<?php

namespace App\Services;

use App\Imports\BingReportImport;
use App\Models\Account;
use App\Models\Services\BingReportService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZanySoft\Zip\Zip;

class BingService
{
    /**
     * @var Account $account
     */
    private $account;

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var string $uri
     */
    private $uri;

    /**
     * @var string $token
     */
    private $token;

    /**
     * @var string $file_path
     */
    private $file_path;

    /**
     * @var string $filename
     */
    private $filename;

    public static function resolve(Account $account): BingService
    {
        /** @var BingService $bingService */
        $bingService = app(self::class);
        $bingService->account = $account;
        $bingService->client = new Client();
        $bingService->uri = 'https://adunit-perf.ask.com/v1/bingrevenue';
        $bingService->token = $account->report_token;
        $bingService->file_path = 'public/bing/';
        $bingService->filename = 'bingrevenue_' . date('Y-m-d');

        return $bingService;
    }

    public function getReport(): array
    {
        $response = $this->downloadReport();

        if (! empty($response->message)) {
            if (! $this->downloadAndExtractZipFile($response->message->downloadUrl)) {
                return [
                    'success' => false,
                    'message' => 'Failed to extract zip file'
                ];
            }

            if (! $this->extractAndStoreCSVData($response)) {
                return [
                    'success' => false,
                    'message' => 'Failed to extract csv file'
                ];
            }
        }

        Log::info('finish bing report - ' . date('Y-m-d'));
        return [
            'success' => true,
            'message' => 'Success'
        ];
    }

    private function downloadReport()
    {
        $base_url = "{$this->uri}?token={$this->token}";

        try {
            Log::info('start curl bing - ' . date('Y-m-d'));

            $response = $this->client->request('GET', $base_url);
            return json_decode($response->getBody());
        } catch (\Throwable $exception) {
            // gateway error then retry
            if ($exception->getCode() === 504) {
                $this->downloadReport();
            }

            Log::error('failed bing to extract zip file: ' . $exception->getMessage());
        }

        return false;
    }

    private function downloadAndExtractZipFile(string $url): bool
    {
        $response = $this->client->request('GET', $url);

        Storage::put("{$this->file_path}{$this->filename}.zip", $response->getBody());

        try {
            Log::info('start bing download zip file - ' . date('Y-m-d'));

            $file_path = storage_path("app/{$this->file_path}");

            $zip = Zip::open("{$file_path}{$this->filename}.zip");
            $zip->extract($file_path);

            // although it will return only 1 csv file, use loop just to make it easy to get the filename
            foreach ($zip->listFiles() as $file) {
                rename("{$file_path}{$file}", "{$file_path}{$this->filename}.csv");
            }

            $zip->close();
        } catch (\Throwable $exception) {
            Log::error('failed bing download zip file: ' . $exception->getMessage());
            return false;
        }

        return true;
    }

    private function extractAndStoreCSVData(object $response): bool
    {
        DB::beginTransaction();

        try {
            Log::info('start bing extract csv file- ' . date('Y-m-d'));

            (new BingReportImport($this->account, $response))->import("{$this->file_path}{$this->filename}.csv", 'local');

            // file no longer needed, delete after import
//            Storage::delete(["{$this->file_path}{$this->filename}.csv", "{$this->file_path}{$this->filename}.zip"]);
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('failed bing extract csv file: ' . $exception->getMessage());
            return false;
        }

        return true;
    }
}

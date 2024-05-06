<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AdLibraryService
{
    private $client;
    private $uri;

    public static function resolve(): AdLibraryService
    {
        $AdLibraryService = app(self::class);
        $AdLibraryService->client = new Client();

        return $AdLibraryService;
    }

    public function getAdLibrary()
    {
        $base_url = "http://3.133.91.31";

        Log::info('adlibrary enpoint - ' . $base_url);

        $adList = [
            'data' => [],
            'total' => 0
        ];

        try {
            Log::info('start curl facebook ad library - ' . date('Y-m-d'));

            $response = $this->client->request('GET', $base_url);
            $result = json_decode($response->getBody());

            $adList['data'] = $result->results;
            $adList['total'] = $result->count;
        } catch (\Throwable $exception) {
            Log::error('failed curl facebook ad library: ' . $exception->getMessage());
        }

        return $adList;
    }
}

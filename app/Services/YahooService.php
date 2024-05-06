<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Enums\YahooReportTypeEnum;
use App\Models\Services\YahooReportService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReportImport;

/**
 * Class YahooService.
 */
class YahooService
{
    private $account;
    private $client;
    private $uri;
    private $token;

    private $auth_uri;
    private $report_uri;
    private $client_id = '';
    private $client_secret = '';
    private $grant_type = 'client_credentials';

    public static function resolveAMG(Account $account): YahooService
    {
        /** @var YahooService $yahooService */
        $yahooService = app(self::class);
        $yahooService->account = $account;
        $yahooService->client = new Client();
        $yahooService->uri = 'https://adunit-perf.ask.com/v1/yahoo-reports/';
        $yahooService->token = $account->report_token;

        return $yahooService;
    }

    public static function resolveDDC(Account $account): YahooService
    {
        /** @var YahooService $yahooService */
        $yahooService = app(self::class);
        $yahooService->account = $account;
        $yahooService->client = new Client();
        $yahooService->uri = 'https://rdp.ddc.com/api/v1/dw/';

        return $yahooService;
    }

    public static function resolve(): YahooService
    {
        $yahooService = app(self::class);
        $yahooService->client = new Client();
        $yahooService->auto_uri = "";
        $yahooService->report_uri = "";
        $yahooService->token = "";
        $yahooService->client_id = "766936ee-be14-4eb4-a9ad-d6e8abd406f0";
        $yahooService->client_secret = "n1yqMHlDrg6efOhYH6Tkc+M/LhqBZMFpBA4gBI6z+fiIRRoaQg";

        return $yahooService;
    }

    public function getTypeReport(): string
    {
        $base_url = "{$this->uri}type?token={$this->token}";

        Log::info('start yahoo type curl - ' . date('Y-m-d'));
        $response = $this->getRequestClient($base_url);

        foreach ($response['body'] as $item) {
            $item->DATA_HOUR = $this->parseDataHourValue($item->DATA_HOUR);
            YahooReportService::create($this->account, YahooReportTypeEnum::TYPE(), (array) $item);
        }

        Log::info('done yahoo type curl - ' . date('Y-m-d'));
        return 'Success';
    }

    public function getSourceReport(): string
    {
        $base_url = "{$this->uri}source?token={$this->token}";

        Log::info('start yahoo source curl - ' . date('Y-m-d'));
        $response = $this->getRequestClient($base_url);

        foreach ($response['body'] as $item) {
            $item->DATA_HOUR = $this->parseDataHourValue($item->DATA_HOUR);
            YahooReportService::create($this->account, YahooReportTypeEnum::SOURCE(), (array) $item);
        }

        Log::info('done yahoo source curl - ' . date('Y-m-d'));
        return 'Success';
    }
    
    public function getDDCReport(): string
    {
        $token_url = "https://rdp.ddc.com/oauth2/access_token?code=5631f4ff12c5eb3f7be522ecd91bb231";
        $response = $this->getRequestClient($token_url);
        $this->token = $response['body']->access_token;

        $base_url = "{$this->uri}yss_current_day?access_token={$this->token}";
        
        Log::info('start yahoo ddc curl - ' . date('Y-m-d'));
        $response = $this->client->request('GET', $base_url);

        $row = explode("\n", $response->getBody());

        for ($i=2; $i<count($row)-1; $i++) {
            
            $data = explode("	", $row[$i]);
            
            $reportData = [
                'update_date' => $data[0].' '.$data[1].':00:00',
                'domain' => $data[2],
                'country_code' => $data[3],
                'device_type' => $data[4],
                'campaign' => $data[5],
                'searches' => $data[6],
                'clicks' => $data[7],
                'revenue' => $data[8],
                'tq' => $data[9],
                'coverage' => $data[10]
            ];

            YahooReportService::create_ddc($this->account, (array) $reportData);
        }

        Log::info('done yahoo ddc curl - ' . date('Y-m-d'));

        return 'Success';
    }

    private function getRequestClient(string $url, array $options = []): array
    {
        $response = $this->client->request('GET', $url, $options);
        Log::info('finish yahoo curl request - ' . date('Y-m-d'));

        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getReasonPhrase(),
            'body' => json_decode($response->getBody()),
        ];
    }

    private function parseDataHourValue($value): string
    {
        if (strlen($value) === 10) {
            $value = "{$value}0000";
        } elseif (strlen($value) === 12) {
            $value = "{$value}00";
        }

        return $value;
    }

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function prepareSignedJWT()
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $body = [
            'aud' => 'https://id.b2b.yahooinc.com/identity/oauth2/access_token?realm=dsp',
            'iss' => $this->client_id,
            'sub' => $this->client_id,
            'iat' => time(),
            'exp' => time() + 600
        ];

        //$encodedHeader = base64_encode(json_encode($header));
        //$encodedBody = base64_encode(str_replace('\/', "/", json_encode($body)));
        $encodedHeader = $this->base64url_encode(json_encode($header));
        $encodedBody = $this->base64url_encode(json_encode($body));

        $jwt_signing_string = $encodedHeader . '.' . $encodedBody;

        $jwt_signature = $this->base64url_encode(hash_hmac('sha256', $jwt_signing_string, $this->client_secret, true));

        return $jwt_signing_string . '.' . $jwt_signature;
    }

    private function getAccessToken()
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://id.b2b.yahooinc.com/identity/oauth2/access_token',
                [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                        'client_assertion' => $this->prepareSignedJWT(),
                        'scope' => 'dsp-api-access',
                        'realm' => 'dsp'
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ]
                ]
            );

            //$result = json_decode($response->getBody());

            return $response;
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'response' => $exception->getMessage()
            ]);
        }

        return $result;
    }

    public function collectionAllData($from, $to)
    {
        $import = new ReportImport();
        //$import->onlySheets('Data');
        $rows = Excel::toArray($import, storage_path('app/public/pmc-2023-03-10-06-42-18.xls'));

        $result = [];

        $link_id_arry = config('linkid.link_type_uid');
        $link_campaign_arry = config('linkid.link_campaign');

        $total = [
            'revenue' => 0,
            'ppc' => 0,
            'ctr' => 0
        ];

        foreach ($rows['Data'] as $row) 
        {
            $link_id = substr(explode(".", $row['type_tag'])[0], 2);

            $index = array_search($link_id, $link_id_arry);

            if ($index) {
                $result[] = [
                    'date' => $row['date'],
                    'type_tag' => $row['type_tag'],
                    'campaign_name' => $link_campaign_arry[$index],
                    'searches' => $row['searches'],
                    'bidded_searches' => $row['bidded_searches'],
                    'bidded_results' => $row['bidded_results'],
                    'bidded_clicks' => $row['bidded_clicks'],
                    'revenue' => $row['estimated_gross_revenue'],
                    'coverage' => $row['coverage'],
                    'ctr' => $row['ctr'],
                    'ppc' => $row['ppc']
                ];

                $total['ctr'] += $row['ctr'];
                $total['ppc'] += $row['ppc'];
                $total['revenue'] += $row['estimated_gross_revenue'];
            }
        }

        return [
            'data' => $result,
            'grandTotal' => $total,
            'total' => count($result)
        ];
    }
}

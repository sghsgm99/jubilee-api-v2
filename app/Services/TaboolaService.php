<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TaboolaService
{
    private $client;
    private $auth_uri;
    private $report_uri;
    private $client_id = '';
    private $client_secret = '';
    private $grant_type = 'client_credentials';

    public static function resolve(): TaboolaService
    {
        $taboolaService = app(self::class);
        $taboolaService->client = new Client();
        $taboolaService->auto_uri = "https://backstage.taboola.com/backstage/oauth/token";
        $taboolaService->report_uri = "https://backstage.taboola.com/backstage/api/1.0/allresponsemediaindia-usd-network/reports/campaign-summary/dimensions/campaign_day_breakdown";
        $taboolaService->client_id = "8be4f8b2537d4a4baed27de52e83e36a";
        $taboolaService->client_secret = "a4ec2a5aac544c4ba98a2486ad2a6e62";

        return $taboolaService;
    }

    private function getToken()
    {
        $options = [
            'form_params' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => $this->grant_type
            ]
        ];

        try {
            $response = $this->client->request('POST', $this->auto_uri, $options);

            $result = json_decode($response->getBody());
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'payload' => $options,
                'response' => $exception->getMessage()
            ]);
        }

        return $result;
    }

    public function collectionAllData($from, $to)
    {
        $infos = [];

        $url = "{$this->report_uri}?start_date={$from}&end_date={$to}";
        $token = $this->getToken();

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token->{'access_token'}
            ]
        ];

        try {
            $response = $this->client->request('GET', $url, $options);

            $result = json_decode($response->getBody());
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'payload' => $options,
                'response' => $exception->getMessage()
            ]);
        }

        $res_result = [];

        foreach ($result->results as $v) {
            $res_result[] = [
                'date' => $v->{'date'},
                'campaign_name' => $v->{'campaign_name'},
                'impressions' => $v->{'impressions'},
                'clicks' => $v->{'clicks'},
                'spent' => $v->{'spent'},
                'ctr' => $v->{'ctr'},
                'cpm' => $v->{'cpm'},
                'cpc' => $v->{'cpc'}
            ];
        }

        return $res_result;
    }
}

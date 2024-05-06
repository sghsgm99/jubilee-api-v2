<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MediaNetService
{
    private $client;
    private $uri;
    private $customer_guid = '';
    private $customer_key = '';
    private $max_page_size = 500;

    public static function resolve(): MediaNetService
    {
        $mediaService = app(self::class);
        $mediaService->client = new Client();
        $mediaService->uri = config('report.media.uri');
        $mediaService->customer_guid = config('report.media.customer_guid');
        $mediaService->customer_key = config('report.media.customer_key');
        $mediaService->max_page_size = config('report.media.max_page_size');

        return $mediaService;
    }

    public function collectionAllData($from)
    {
        $totalPages = 1;
        $infos = [];

        $base_url = "{$this->uri}?customer_guid={$this->customer_guid}&customer_key={$this->customer_key}&from_date={$from}&to_date={$from}&page_size={$this->max_page_size}";

        for ($i=1; $i<=$totalPages; $i++) {
            $url = $base_url."&page_number=".$i;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);

            $data = curl_exec($ch);
            curl_close($ch);

            $xml = simplexml_load_string($data);
            $json = json_encode($xml);
            $list = json_decode($json, true);

            $totalPages = $list['@attributes']['totalPages'];

            foreach ($list['statsData']['reportItem'] as $item) {
                $revenue = floatval(substr($item['@attributes']['estimatedRevenue'], 1));

                if ($revenue > 0) {
                    if (isset($infos[$item['@attributes']['channelName2']])) {
                        $dd = $infos[$item['@attributes']['channelName2']]['detail'];
                        $dd[] = [
                            'date' => $item['@attributes']['date'],
                            'revenue' => $revenue,
                            'rpc' => round($revenue / $item['@attributes']['totalClicks'], 2)
                        ];

                        $infos[$item['@attributes']['channelName2']] = [
                            'impressions' => $infos[$item['@attributes']['channelName2']]['impressions'] + $item['@attributes']['impressions'],
                            'totalClicks' => $infos[$item['@attributes']['channelName2']]['totalClicks'] + $item['@attributes']['totalClicks'],
                            'estimatedRevenue' => $infos[$item['@attributes']['channelName2']]['estimatedRevenue'] + $revenue,
                            'detail' => $dd
                        ];
                    } else {
                        $d = [];
                        $d[] = [
                            'date' => $item['@attributes']['date'],
                            'revenue' => $revenue,
                            'rpc' => round($revenue / $item['@attributes']['totalClicks'], 2)
                        ];

                        $infos[$item['@attributes']['channelName2']] = [
                            'impressions' => $item['@attributes']['impressions'],
                            'totalClicks' => $item['@attributes']['totalClicks'],
                            'estimatedRevenue' => $revenue,
                            'detail' => $d
                        ];
                    }
                }
            }
        }

        $mediaList = [];

        foreach ($infos as $key => $v) {
            $mediaList[] = [
                'keyword' => $key,
                'impressions' => $v['impressions'],
                'totalClicks' => $v['totalClicks'],
                'estimatedRevenue' => round($v['estimatedRevenue'], 2),
                'detail' => $v['detail'],
                'rpc' => round($v['estimatedRevenue'] / $v['totalClicks'], 2)
            ];
        }

        return [
            'data' => $mediaList,
            'grandTotal' => [
                'impressions' => $list['grandTotal']['@attributes']['impressions'],
                'totalClicks' => $list['grandTotal']['@attributes']['totalClicks'],
                'estimatedRevenue' => $list['grandTotal']['@attributes']['estimatedRevenue']
            ],
            'total' => count($mediaList)
        ];
    }
}

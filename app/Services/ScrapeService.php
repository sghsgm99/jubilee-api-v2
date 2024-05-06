<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ScrapeService
{
    private $client;
    private $uri;
    private $apiKey = '';

    public static function resolve(): ScrapeService
    {
        $scrapeService = app(self::class);
        $scrapeService->client = new Client();
        $scrapeService->uri = config('creative.endpoint.uri');
        $scrapeService->apiKey = config('creative.scrapingbee.api_key');

        return $scrapeService;
    }

    private function getCookies()
    {
        $cookie_result = [
            'hash' => null,
            'sid' => null
        ];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://app.scrapingbee.com/api/v1?url=https://adheart.me/login&premium_proxy=True&api_key='.$this->apiKey);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
		  'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
		]);
		curl_setopt($ch, CURLOPT_HEADER, 1);

		$body = [
		  'email'=> config('creative.adheart.login.email'),
		  'password'=> config('creative.adheart.login.password')
		];
		$body = http_build_query($body);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

		$response = curl_exec($ch);

		if (!$response) {
		  return $cookie_result;
		}

		$lines = explode("\n", $response);
		$headers = array();
		$body = "";
		foreach($lines as $num => $line){
			$l = str_replace("\r", "", $line);
			if(trim($l) == ""){
				$headers = array_slice($lines, 0, $num);
				$body = $lines[$num + 1];
				$cookies = preg_grep('/^Set-Cookie:/', $headers);
				break;
			}
		}

		foreach($cookies as $c){
			if (preg_match('/hash=(.*?);/', $c, $match) == 1) {
				$cookie_result['hash']= $match[1];
			}
			
			if (preg_match('/PHPSESSID=(.*?);/', $c, $match) == 1) {
				$cookie_result['sid']= $match[1];
			}
		}
		
		return $cookie_result;
    }

    public function getCampaignCollections(
        $currentPage = 1, $through = null, $in_link = null, 
        $sel_through = null, $sel_geo = null, $geo_to = null, 
        $ip = null, $pid = null, $perPage = null)
    {
        $cookies = $this->getCookies();

        Log::info('adheart login cookies - ' . implode(',', $cookies));
        
        $base_url = "{$this->uri}?in_text={$through}&in_links={$in_link}&geos={$sel_geo}&geos_cnt={$geo_to}&fb_page_id={$pid}&ip={$ip}&page={$currentPage}";

        $creativeList = [
            'data' => [],
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => 0
            ]
        ];

        $options = [
            'json' => $cookies
        ];

        try {
            Log::info('start curl adheart - ' . date('Y-m-d'));

            $response = $this->client->request('GET', $base_url, $options);
            $result = json_decode($response->getBody());

            $creativeList['data'] = $result->results;
            $creativeList['meta']['total'] = $result->count;
        } catch (\Throwable $exception) {
            
            Log::error('failed curl adheart: ' . $exception->getMessage());
        }

        return $creativeList;
    }
}

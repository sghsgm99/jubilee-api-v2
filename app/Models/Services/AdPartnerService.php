<?php

namespace App\Models\Services;

use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\AdPartner;
use App\Models\BlackList;
use App\Models\KeywordSpinning;
use App\Models\Services\KeywordSpinningService;
use App\Models\Enums\SerpCategoryEnum;
use App\Models\Site;
use App\Services\ResponseService;

class AdPartnerService extends ModelService
{
    private $useragent;
    private $advert;

    private function getRandomUserAgent()
    {
        $file = file_get_contents(storage_path("app/user-agents_true_true.json"));
        $userAgents = json_decode($file);

        $useragent = trim($userAgents[array_rand($userAgents)]);

        return urlencode($useragent);
    }

    private function checkYahooBlackList($res, $query)
    {
        $ads = json_decode(json_encode($res), true);

        foreach($ads as $item) {
            $url = $item['@attributes']['siteHost'];

            if (BlackList::searchEx($url) > 0 && KeywordSpinning::searchEx($query, $url, SerpCategoryEnum::YAHOO) == 0) {
                $keyword = $query;
                $domain = str_ireplace('www.', '', $url);
                $adver = explode('.', $domain)[0];

                $keywordspinning = KeywordSpinningService::create(
                    SerpCategoryEnum::YAHOO,
                    $keyword,
                    $adver,
                    $url
                );
            }
        }
    }

    public function fetchAMGYahooAds($query, $ocode, $rty)
    {
        try {
            if ($query) {
                $this->useragent = $this->getRandomUserAgent();

                $q = preg_replace('~\s~', '%20', $query);

                $this->amgApiYahooConnect($q, $ocode, $rty);

                $this->checkYahooBlackList($this->advert, $query);

                return response()->json(['ads' => $this->advert, 'query' => $query]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 422);
        }
    }

    public function fetchAMGYahooAdsEx($query)
    {
        try {
            if ($query) {
                $this->useragent = $this->getRandomUserAgent();

                $q = preg_replace('~\s~', '%20', $query);

                $this->amgApiYahooConnect($q, null, null);

                return $this->advert;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    private function amgApiYahooConnect($query, $o, $r)
    {
        $adp = AdPartner::where('partner', 'Yahoo')->first();

        $token_key = $adp->config['token_key'];
        $ocode = isset($o) ? $o : $adp->config['ocode'];
        $serve_url = $adp->config['serve_url'];
        $rty = isset($r) ? $r : null;

        $context = stream_context_create(
            array(
                'http' => array('header' =>
                        [
                            "Accept: application/xml",
                            "amg-key: {$token_key}",
                            "User-Agent: {$this->useragent}"
                        ]
                    )
                )
            );

        $url = "https://dabu.askmediagroup.com/api/v2/ta/yahoo?o={$ocode}&q={$query}&rty={$rty}&serveUrl={$serve_url}";

        $xml = file_get_contents($url, false, $context);
        $xmls = simplexml_load_string($xml);
        $json = json_encode($xmls);
        $response = json_decode($json);
        $this->response = $response;
        $this->getAds();

        return $response;
    }

    private function getAds()
    {
        if (!$this->response) {
            throw new \Exception("No search results");
        }

        if (!isset($this->response->ResultSet)) {
            throw new \Exception("No search results");
        }

        foreach ($this->response->ResultSet as $res) {
            if (!isset($res->Listing)) {
                throw new \Exception("No search results");
            }

            if (is_array($res->Listing) || is_object($res->Listing)) {
                foreach ($res->Listing as $r) {
                    $impid = $r->{'@attributes'}->ImpressionId;
                    if (!empty($impid)) {
                        $this->advert[] = $r;
                    }
                }
            }
        }

        if (!count($this->advert)) {
            throw new \Exception("No sponsored results");
        }
    }

    private function checkBingBlackList($res, $query)
    {
        $ads = json_decode(json_encode($res), true);

        foreach($ads as $item) {
            $url = $item['displayUrl'];

            if (BlackList::searchEx($url) > 0 && KeywordSpinning::searchEx($query, $url, SerpCategoryEnum::BING) == 0) {
                $keyword = $query;
                $domain = str_ireplace('www.', '', $url);
                $adver = explode('.', $domain)[0];

                $keywordspinning = KeywordSpinningService::create(
                    SerpCategoryEnum::BING,
                    $keyword,
                    $adver,
                    $url
                );
            }
        }
    }

    public function fetchAMGBingAds($query, $ocode, $rtb)
    {
        try {
            if ($query) {
                $this->useragent = $this->getRandomUserAgent();

                $q = preg_replace('~\s~', '%20', $query);

                $response = $this->amgBingApiConnect($q, $ocode, $rtb);

                $this->checkBingBlackList($response, $query);

                return response()->json(['ads' => $response, 'query' => $query]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 422);
        }
    }

    public function fetchAMGBingAdsEx($query)
    {
        try {
            if ($query) {
                $this->useragent = $this->getRandomUserAgent();

                $q = preg_replace('~\s~', '%20', $query);

                return $this->amgBingApiConnect($q, null, null);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 422);
        }
    }

    private function amgBingApiConnect($query, $o, $r)
    {
        $adp = AdPartner::where('partner', 'bing')->first();

        $ocode = isset($o) ? $o : $adp->config['ocode'];
        $serve_url = $adp->config['serve_url'];
        $rtb = isset($r) ? $r : null;

        $context = stream_context_create(
            array(
                'http' => array('header' =>
                        [
                            "Accept: application/xml",
                            "User-Agent: {$this->useragent}"
                        ]
                    )
                )
            );

        $url = "https://dabu.askmediagroup.com/api/v2/ta/bing?o={$ocode}&q={$query}&rtb={$rtb}&serveUrl={$serve_url}";

        $data = file_get_contents($url, false, $context);
        $json_data = json_decode($data);

        return $json_data->results->value;
    }

    public static function getIPDetails($ip)
    {
        $city = 'unknown';

        if ($ip == '127.0.0.1')
            $city = 'localhost';
        else {
            $url = "https://api.ipdata.co/{$ip}?api-key=8c56b39e602fc38c570fe55c5f37d70205a3861bbf1252444edbd26b";
            $response = file_get_contents($url);

            if ($response) {
                $ipDetails = json_decode($response, true);
                $city = str_replace("City", "", $ipDetails['city']);
            }
        }

        return response()->json(['city' => $city]);
    }

    public static function updateYahoo(string $token_key, string $ocode, string $serve_url)
    {
        $update_ad = [
            'partner' => 'Yahoo',
            'config' =>
                [
                    'token_key' => $token_key,
                    'ocode' => $ocode,
                    'serve_url' => $serve_url,
                ]
        ];

        $adpartner = AdPartner::where('partner', '=' , $update_ad['partner'])->firstOrNew();
        $adpartner->partner = $update_ad['partner'];
        $adpartner->config = $update_ad['config'];
        $adpartner->user_id = auth()->user()->id;
        $adpartner->account_id = auth()->user()->account_id;
        $adpartner->save();

        return $adpartner;
    }

    public static function getYahoo()
    {
        $adp = AdPartner::where('partner', 'Yahoo')->first();

        return [
            'token_key' => isset($adp->config['token_key']) ? $adp->config['token_key'] : '',
            'ocode' => isset($adp->config['ocode']) ? $adp->config['ocode'] : '',
            'serve_url' => isset($adp->config['serve_url']) ? $adp->config['serve_url'] : ''
        ];
    }

    public static function updateGoogle(string $ocode, string $serve_url)
    {
        $update_ad = [
            'partner' => 'Google',
            'config' =>
                [
                    'ocode' => $ocode,
                    'serve_url' => $serve_url,
                ]
        ];

        $adpartner = AdPartner::where('partner', '=' , $update_ad['partner'])->firstOrNew();
        $adpartner->partner = $update_ad['partner'];
        $adpartner->config = $update_ad['config'];
        $adpartner->user_id = auth()->user()->id;
        $adpartner->account_id = auth()->user()->account_id;
        $adpartner->save();

        return $adpartner;
    }

    public static function getGoogle()
    {
        $adp = AdPartner::where('partner', 'Google')->first();

        return [
            'ocode' => isset($adp->config['ocode']) ? $adp->config['ocode'] : '',
            'serve_url' => isset($adp->config['serve_url']) ? $adp->config['serve_url'] : ''
        ];
    }

    public static function updateBing(string $ocode, string $serve_url)
    {
        $update_ad = [
            'partner' => 'Bing',
            'config' =>
                [
                    'ocode' => $ocode,
                    'serve_url' => $serve_url,
                ]
        ];

        $adpartner = AdPartner::where('partner', '=' , $update_ad['partner'])->firstOrNew();
        $adpartner->partner = $update_ad['partner'];
        $adpartner->config = $update_ad['config'];
        $adpartner->user_id = auth()->user()->id;
        $adpartner->account_id = auth()->user()->account_id;
        $adpartner->save();

        return $adpartner;
    }

    public static function getBing()
    {
        $adp = AdPartner::where('partner', 'Bing')->first();

        return [
            'ocode' => isset($adp->config['ocode']) ? $adp->config['ocode'] : '',
            'serve_url' => isset($adp->config['serve_url']) ? $adp->config['serve_url'] : ''
        ];
    }

    public function fetchYahooAds(Site $site, $keyword, $max_count, $type, $market, $source, $affil_data_ip, $user_agent)
    {
        if (!$keyword) return [];

        try {
            $company = config('ads.yahoo.company');
            //$mkt = config('ads.yahoo.mkt');
            //$partner = config('ads.yahoo.partner')[$site->name][0];
            $serve_url = urlencode($site->url."/search/top5/");
            //$affil_data = config('ads.yahoo.affil_data');
            $affil_data = 'ip='.$affil_data_ip.'&ua='.$user_agent;

            $url = 
                "https://xml-nar-ss.ysm.yahoo.com/d/search/p/".$company
                ."/xmlb/multi/?Keywords=".$keyword
                ."&mkt=".$market
                ."&serveUrl=".$serve_url
                ."&affilData=".urlencode($affil_data);

            if ($source) $url .= "&Partner=".$source;
            if ($type) $url .= "&type=".$type;

            $xml = file_get_contents($url, false);
            $xmls = simplexml_load_string($xml);
            $json = json_encode($xmls);
            $response = json_decode($json);

            if (!$response) {
                throw new \Exception("No search results");
            }
    
            if (!isset($response->ResultSet)) {
                throw new \Exception("No search results");
            }
    
            foreach ($response->ResultSet as $res) {
                if (!isset($res->Listing)) {
                    throw new \Exception("No search results");
                }
    
                if (is_array($res->Listing) || is_object($res->Listing)) {
                    foreach ($res->Listing as $r) {
                        $result[] = [
                            'title' => $r->{'@attributes'}->title,
                            'description' => $r->{'@attributes'}->description,
                            'url' => $r->{'@attributes'}->siteHost,
                            'click_url' => $r->{'ClickUrl'},
                            'related' => []
                        ];
                        
                        if ($max_count && (count($result) > $max_count - 1))
                            return $result;
                    }
                }
            }
        } catch (\Throwable $exception) {
            return ResponseService::clientError('Bad Request', [
                'response' => $exception->getMessage()
            ]);
        }
        
        return $result;
    }
}

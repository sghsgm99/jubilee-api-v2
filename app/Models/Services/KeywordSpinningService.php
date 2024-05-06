<?php

namespace App\Models\Services;

use App\Models\User;
use Database\Seeders\ChannelSeeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\Services\AdPartnerService;
use App\Models\KeywordSpinning;
use App\Models\Enums\SerpCategoryEnum;

class KeywordSpinningService extends ModelService
{
    private const KEYWORD_LIMIT = 10;

    public static function getYahooActiveList($query)
    {
        $adservice = new AdPartnerService();

        $yahoo_amg_ads = $adservice->fetchAMGYahooAdsEx($query);

        $results = [];
        $i = 0;

        $ads = json_decode(json_encode($yahoo_amg_ads), true);

        foreach($ads as $item) {
            $url = $item['@attributes']['siteHost'];
            $domain = str_ireplace('www.', '', $url);
            $adver = explode('.', $domain);

            $results[] = [
                'id' => $i++,
                'keyword' => $query,
                'advertiser' => $adver[0],
                'url' => $url,
                'clicks' => 0,
                'impr' => 0,
                'ctr' => 0,
                'cpc' => 0,
                'conversion' => 0,
            ];

            if (KeywordSpinning::searchEx($query, $url, SerpCategoryEnum::YAHOO) == 0) {
                $keywordspinning = self::create(
                    SerpCategoryEnum::YAHOO,
                    $query,
                    $adver[0],
                    $url
                );
            }
        }

        return $results;
    }

    private function getBingKeywordToolResult($kw)
    {
        $api_key = config('keyword.keywordtool.api_key');
        $url = config('keyword.keywordtool.endpoints.bing_suggestion');

        $params = [
            'apikey' => $api_key,
            'keyword' => $kw,
            'type' => 'related',
            'metrics' => true,
            'output' => 'json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($output, TRUE);

        $kw_arr = [];

        if (!empty($res["results"])) {
            foreach ($res["results"] as $key => $value) {
                foreach ($value as $item) {
                    if ($item['cmp'] == 'High') {
                        $kw_arr[] = [
                            'kw' => $item['string'],
                            'impr' => $item['volume'],
                            'cpc' => $item['cpc']
                        ];

                        if (count($kw_arr) > self::KEYWORD_LIMIT)
                            return $kw_arr;
                    }
                }
            }
        }

        return $kw_arr;
    }

    private function getBingSerpAd($kw)
    {
        $adservice = new AdPartnerService();

        $bing_amg_ads = $adservice->fetchAMGBingAdsEx($kw);

        $ads = json_decode(json_encode($bing_amg_ads), true);

        foreach($ads as $item) {
            return $item['displayUrl'];
        }

        return '';
    }

    public function getBingActiveList($query)
    {
        $kwtool_result = $this->getBingKeywordToolResult($query);

        $results = [];
        $i = 0;

        foreach($kwtool_result as $item) {
            $url = $this->getBingSerpAd($item['kw']);
            $domain = str_ireplace('www.', '', $url);
            $adver = explode('.', $domain);

            $results[] = [
                'id' => $i++,
                'keyword' => $item['kw'],
                'advertiser' => $adver[0],
                'url' => $url,
                'clicks' => 0,
                'impr' => $item['impr'] ?? 0,
                'ctr' => 0,
                'cpc' => $item['cpc'] ?? 0,
                'conversion' => 0,
            ];

            if (KeywordSpinning::searchEx($item['kw'], $url, SerpCategoryEnum::BING) == 0) {
                $keywordspinning = self::create(
                    SerpCategoryEnum::BING,
                    $item['kw'],
                    $adver[0],
                    $url,
                    $item['impr'] ?? 0,
                    $item['cpc'] ?? 0
                );
            }
        }

        return $results;
    }

    public static function create(
        string $category,
        string $keyword,
        string $advertiser,
        string $url,
        int $impr = 0,
        float $cpc = 0
    )
    {
        $ks = new KeywordSpinning();

        $ks->keyword = $keyword;
        $ks->advertiser = $advertiser;
        $ks->url = $url;
        $ks->category = $category;
        $ks->impr = $impr;
        $ks->cpc = $cpc;
        $ks->account_id = auth()->user()->account_id;
        $ks->save();

        return $ks;
    }
}

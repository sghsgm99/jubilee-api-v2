<?php

namespace App\Services;

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V15\ResourceNames;
use Google\Ads\GoogleAds\V15\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V15\Resources\Campaign;
use Google\Ads\GoogleAds\V15\Services\CampaignOperation;
use Google\Ads\GoogleAds\V15\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V15\Enums\CriterionTypeEnum\CriterionType;
use Google\Ads\GoogleAds\V15\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use Google\Ads\GoogleAds\V15\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V15\GoogleAdsException;
use Google\Ads\GoogleAds\V15\Common\AdTextAsset;
use Google\Ads\GoogleAds\V15\Common\ResponsiveSearchAdInfo;
use Google\Ads\GoogleAds\V15\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V15\Enums\ServedAssetFieldTypeEnum\ServedAssetFieldType;
use Google\Ads\GoogleAds\V15\Resources\Ad;
use Google\Ads\GoogleAds\V15\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V15\Services\AdGroupAdOperation;
use Google\Ads\GoogleAds\V15\Enums\KeywordPlanNetworkEnum\KeywordPlanNetwork;
use Google\Ads\GoogleAds\V15\Services\GenerateKeywordIdeaResult;
use Google\Ads\GoogleAds\V15\Services\KeywordAndUrlSeed;
use Google\Ads\GoogleAds\V15\Services\KeywordSeed;
use Google\Ads\GoogleAds\V15\Services\UrlSeed;
use Google\Ads\GoogleAds\V15\Common\ManualCpc;
use Google\Ads\GoogleAds\V15\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V15\Enums\BudgetDeliveryMethodEnum\BudgetDeliveryMethod;
use Google\Ads\GoogleAds\V15\Resources\Campaign\NetworkSettings;
use Google\Ads\GoogleAds\V15\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V15\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V15\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V15\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V15\Resources\AdGroup;
use Google\Ads\GoogleAds\V15\Services\AdGroupOperation;
use Google\Ads\GoogleAds\V15\Common\KeywordInfo;
use Google\Ads\GoogleAds\V15\Enums\AdGroupCriterionStatusEnum\AdGroupCriterionStatus;
use Google\Ads\GoogleAds\V15\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V15\Services\AdGroupCriterionOperation;
use Google\Ads\GoogleAds\V15\Resources\Campaign\ShoppingSetting;
use Google\ApiCore\ApiException;
use Illuminate\Support\Str;
use DateTime;
use App\Models\Services\AdTemplateService;
use App\Models\KeyIntl;

class GoogleKeywordService extends GoogleService
{
    private const COMPETITION_HIGH = 3;
    private const LANG_US_ID = 1000;
    private const CUSTOMER_ID = '3170615631';
    private const LOC_IDS = array(2840);
    private const CN_LIMIT = 100;
    private const PAGE_SIZE = 10;
    private const PRICE_UNIT = 1000000;

    public function getKeywordHigh(string $kw, int $lvalue)
    {
        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;

        $keywordList = [
            'data' => [],
            'meta' => [
                'total' => 0
            ]
        ];

        $kw_arr = [$kw];

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        foreach ($response->iterateAllElements() as $result) {
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if (($comp > self::COMPETITION_HIGH) && ($lowrange >= $lvalue)) {
                $avg = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getAvgMonthlySearches());
                $highrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getHighTopOfPageBidMicros());
                $highrange = round($highrange / 1000000, 2);
                $keyword = $result->getText();

                $keywordList['data'][] = [
                    'keyword' => $keyword,
                    'avgmonth' => $avg,
                    'lowrange' => '$' . $lowrange,
                    'highrange' => '$' . $highrange,
                    'compet' => 'HIGH'
                ];

                $keywordList['meta']['total']++;
            }
        }

        return $keywordList;
    }

    public function getCampaignNames(string $kw, int $lvalue)
    {
        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;
        $kw_arr = [$kw];

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        $res_result = [
            'cname_arr' => [],
            'kw_arr' => [],
            'intl_arr' => [],
        ];
        $cnt = 0;

        foreach ($response->iterateAllElements() as $result) {
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if (($comp > self::COMPETITION_HIGH) && ($lowrange >= $lvalue)) {
                $keyword = $result->getText();

                $q_res = KeyIntl::where('keyword', $keyword)->first();

                if (empty($q_res)) {
                    $mycams = KeyIntl::all();

                    $tmp_arr = [];
                    foreach ($mycams as $item) {
                        $tmp_arr[] = $item['intl'];
                    }

                    do {
                        $n = mt_rand(1, 1900);
                    } while (in_array($n, $tmp_arr));

                    $res_result['cname_arr'][] = [
                        'value' => $cnt++,
                        'text' => 'INTL' . $n . ' ' . $keyword . ' ($' . $lowrange . ')'
                    ];
                    $res_result['kw_arr'][] = $keyword;
                    $res_result['intl_arr'][] = $n;
                }

                if ($cnt > self::CN_LIMIT)
                    return $res_result;
            }
        }

        return $res_result;
    }

    public function getKeywordLow(int $currentPage, int $perPage, string $kw, int $lvalue)
    {
        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;

        $from_page = ($currentPage - 1) * $perPage - 1;
        $to_page = $currentPage * $perPage;

        $kw_arr = [$kw];

        $keywordList = [
            'data' => [],
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => 0
            ]
        ];

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        $cnt = 0;
        $sem_kw = "";

        foreach ($response->iterateAllElements() as $result) {
            $avg = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getAvgMonthlySearches());
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if ($comp > 3 && $lowrange < $lvalue && $avg > 1000) {
                if (($cnt > $from_page) && ($cnt < $to_page)) {
                    $highrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getHighTopOfPageBidMicros());
                    $highrange = round($highrange / 1000000, 2);
                    $keyword = $result->getText();

                    $keywordList['data'][] = [
                        'keyword' => $keyword,
                        'avgmonth' => $avg,
                        'lowrange' => '$' . $lowrange,
                        'highrange' => '$' . $highrange,
                        'compet' => 'HIGH',
                        'sem_cpc' => '-',
                        'action' => $keyword
                    ];

                    $sem_kw .= $keyword . ";";
                }
                $cnt++;
            }
        }

        $keywordList['meta']['total'] = $cnt;

        return $keywordList;
    }

    public function getCampaignReport($from, $to)
    {
        $customerId = self::CUSTOMER_ID;

        $googleAdsServiceClient = $this->googleAdsClient->getGoogleAdsServiceClient();

        $query =
            "SELECT campaign.id, campaign.name, segments.date, 
                    metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.conversions, metrics.ctr
            FROM campaign 
            WHERE segments.date >= '".$from."' AND segments.date <= '".$to."' 
            ORDER BY segments.date DESC";

        $response =
            $googleAdsServiceClient->search($customerId, $query);

        $res_result = [];

        foreach ($response->iterateAllElements() as $googleAdsRow) {

            $camp_id = $googleAdsRow->getCampaign()->getId();
            $camp_name = $googleAdsRow->getCampaign()->getName();
            $seg_date = $googleAdsRow->getSegments()->getDate();
            $impr = $googleAdsRow->getMetrics()->getImpressions();
            $click = $googleAdsRow->getMetrics()->getClicks();
            $cost = $googleAdsRow->getMetrics()->getCostMicros() / self::PRICE_UNIT;
            $ctr = $googleAdsRow->getMetrics()->getCtr();
            $conv = $googleAdsRow->getMetrics()->getConversions();

            $res_result[] = [
                'date' => $seg_date,
                'campaign_id' => $camp_id,
                'campaign_name' => $camp_name,
                'impressions' => $impr,
                'clicks' => $click,
                'cost_micros' => $cost,
                'ctr' => $ctr,
                'conversions' => $conv,
            ];
        }

        return $res_result;
    }
}

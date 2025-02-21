<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Services\AdPartnerService;
use App\Models\Services\GoogleCampaignLogService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Log;

class AdServiceController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('adpartners/amgYahooAds', [AdServiceController::class, 'getAMGYahooAds']);
        Route::get('adpartners/amgBingAds', [AdServiceController::class, 'getAMGBingAds']);
        // Route::get('adpartners/amgGoogleAds', [AdServiceController::class, 'getAMGGoogleAds']);
        // Route::get('adpartners/ipLocation', [AdServiceController::class, 'getIPLocation']);
    }

    public static function webhooks()
    {
        Route::get('adpartners/amgYahooAds', [AdServiceController::class, 'getAMGYahooAds']);
        Route::get('adpartners/amgBingAds', [AdServiceController::class, 'getAMGBingAds']);
        Route::get('adpartners/amgGoogleAds', [AdServiceController::class, 'getAMGGoogleAds']);
        Route::get('adpartners/ipLocation', [AdServiceController::class, 'getIPLocation']);
        Route::get('adpartners/{site}/fetchYahooAds', [AdServiceController::class, 'fetchYahooAds']);
        Route::get('adpartners/{site}/postAdClick', [AdServiceController::class, 'postAdClick']);        
    }

    public function getAMGYahooAds(Request $request)
    {
        $query = $request->input('q');
        $ocode = $request->input('ocode');
        $rty = $request->input('rty');

        $adservice = new AdPartnerService();

        $yahoo_amg_ads = $adservice->fetchAMGYahooAds($query, $ocode, $rty);

        return $yahoo_amg_ads;
    }

    public function getAMGBingAds(Request $request)
    {
        $query = $request->input('q');
        $ocode = $request->input('ocode');
        $rtb = $request->input('rtb');

        $adservice = new AdPartnerService();

        $bing_amg_ads = $adservice->fetchAMGBingAds($query, $ocode, $rtb);

        return $bing_amg_ads;
    }

    public function getAMGGoogleAds(Request $request)
    {
        return AdPartnerService::getGoogle();
    }

    public function getIPLocation(Request $request)
    {
        $ip = $request->ip();

        return AdPartnerService::getIPDetails($ip);
    }

    public function fetchYahooAds(Request $request, Site $site)
    {
        $keyword = $request->input('s');
        $max_count = $request->input('n', null);
        $type = $request->input('t', null);
        $market = $request->input('mkt', null);
        $source = $request->input('src', null);
        $affil_data_ip = $request->ip();
        $user_agent = $request->userAgent();

        //Log::info('IP: ' . $affil_data_ip . ', User Agent: '  . $user_agent);

        $adservice = new AdPartnerService();

        return $adservice->fetchYahooAds($site, $keyword, $max_count, $type, $market, $source, $affil_data_ip, $user_agent);
    }

    public function postAdClick(Request $request, Site $site)
    {
        GoogleCampaignLogService::create(
            $request->ip(),
            $request->input('url'),
            $request->userAgent(),
            $request->input('referrer', null),
            $request->input('t', null),
            $request->input('pos', null)
        );

        return ResponseService::success('Google Campaign Log was created.');

        /*Log::info(sprintf(
            "%s%s%s%s%s%s%s%s",
            $request->input('url', null),
            PHP_EOL,
            $request->ip(),
            PHP_EOL,
            $request->userAgent(),
            PHP_EOL,
            //$request->headers->get('referer'),
            $request->input('referrer', null),
            PHP_EOL
        ));*/
    }
}

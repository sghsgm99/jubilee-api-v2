<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAnalyticRequest;
use App\Models\Site;
use App\Services\AnalyticsConsolidatedService;
use App\Services\ResponseService;

class SiteAnalyticController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('site-analytics/{site}/overview', [SiteAnalyticController::class, 'getOverview']);
        Route::get('site-analytics/{site}/top-referrers', [SiteAnalyticController::class, 'getTopReferrers']);
        Route::get('site-analytics/{site}/session-country', [SiteAnalyticController::class, 'getSessionCountry']);
        Route::get('site-analytics/{site}/session-device', [SiteAnalyticController::class, 'getSessionDevice']);
        Route::get('site-analytics/{site}/traffic-channel', [SiteAnalyticController::class, 'getTrafficChannel']);
        Route::get('site-analytics/{site}/page-content', [SiteAnalyticController::class, 'getPageContent']);
        Route::get('site-analytics/{site}/active-users', [SiteAnalyticController::class, 'getVisitorAndPageViews']);
        Route::get('site-analytics/{site}/text-info', [SiteAnalyticController::class, 'getSummaryTextInfo']);

        // Real Time Analytic Routes
        Route::get('site-analytics/{site}/real-time-data', [SiteAnalyticController::class, 'getRealTimeData']);

        // Consolidated Analytic Routes
        Route::get('site-analytics/overall-sites', [SiteAnalyticController::class, 'getAllSites']);
        Route::get('site-analytics/overall-overview', [SiteAnalyticController::class, 'getOverallOverview']);
        Route::get('site-analytics/overall-top-referrers', [SiteAnalyticController::class, 'getOverallTopReferrers']);
        Route::get('site-analytics/overall-session-country', [SiteAnalyticController::class, 'getOverallSessionCountry']);
        Route::get('site-analytics/overall-session-device', [SiteAnalyticController::class, 'getOverallSessionDevice']);
        Route::get('site-analytics/overall-traffic-channel', [SiteAnalyticController::class, 'getOverallTrafficChannel']);
        Route::get('site-analytics/overall-page-content', [SiteAnalyticController::class, 'getOverallPageContent']);
        Route::get('site-analytics/overall-active-users', [SiteAnalyticController::class, 'getOverallVisitorAndPageViews']);
        Route::get('site-analytics/overall-text-info', [SiteAnalyticController::class, 'getOverallSummaryTextInfo']);
    }

    public function getOverview(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getTotalVisitorsAndPageViews($from, $to);
    }

    public function getTopReferrers(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getTopReferrers($from, $to, 5);
    }

    public function getSessionCountry(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getSessionByCountry($from, $to);
    }

    public function getSessionDevice(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getSessionByDevice($from, $to);
    }

    public function getTrafficChannel(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getTrafficSource($from, $to);
    }

    public function getPageContent(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getMostVisitedPages($from, $to);
    }

    public function getVisitorAndPageViews(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getMonthlyWeeklyDailyVisitors($from, $to);
    }

    public function getSummaryTextInfo(SiteAnalyticRequest $request, Site $site)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());

        return $site->AnalyticService()->getSummaryInfo($from, $to);
    }

    public function getRealTimeData(Site $site)
    {
        return $site->AnalyticService()->getRealTimeData();
    }

    public function getAllSites()
    {
        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getAllSitesAvailable();
    }

    public function getOverallOverview(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallTotalVisitorsAndPageViews($from, $to, $hostname);
    }

    public function getOverallTopReferrers(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallTopReferrers($from, $to, $hostname);
    }

    public function getOverallSessionCountry(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallSessionByCountry($from, $to, $hostname);
    }

    public function getOverallSessionDevice(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallSessionByDevice($from, $to, $hostname);
    }

    public function getOverallTrafficChannel(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallTrafficSource($from, $to, $hostname);
    }

    public function getOverallPageContent(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallMostVisitedPages($from, $to, $hostname);
    }

    public function getOverallVisitorAndPageViews(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallMonthlyWeeklyDailyVisitors($from, $to, $hostname);
    }

    public function getOverallSummaryTextInfo(SiteAnalyticRequest $request)
    {
        $from = Carbon::parse($request->validated()['from'] ?? now()->subDays(7));
        $to = Carbon::parse($request->validated()['to'] ?? now());
        $hostname = $request->validated()['hostname'] ?? null;

        return ResponseService::serviceUnavailable();

        return AnalyticsConsolidatedService::resolve(auth()->user()->account)
            ->getOverallSummaryInfo($from, $to, $hostname);
    }
}

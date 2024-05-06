<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\BingReport;
use App\Models\YahooReport;
use App\Models\GoogleReport;
use App\Models\ClickscoReport;
use Illuminate\Http\Request;
use App\Models\YahooDDCReport;
use App\Models\ProgrammaticReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExportBingReportRequest;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\BingReportResource;
use App\Http\Resources\YahooReportResource;
use App\Http\Resources\GoogleReportResource;
use App\Http\Resources\ClickscoReportResource;
use App\Models\Enums\YahooDDCReportTypeEnum;
use App\Models\Enums\ReportPlatformEnum;
use App\Models\Enums\BingReportTypeEnum;
use App\Models\Enums\YahooReportTypeEnum;
use App\Models\Services\BingReportService;
use App\Models\Services\GoogleReportService;
use App\Models\Services\YahooReportService;
use App\Http\Requests\ExportGoogleReportRequest;
use App\Http\Requests\ExportYahooAmgReportRequest;
use App\Http\Requests\ExportYahooDDCReportRequest;
use App\Http\Resources\ProgrammaticReportResource;
use App\Services\MediaNetService;
use App\Services\GoogleService1;
use App\Services\TaboolaService;
use App\Services\YahooService;
use App\Services\ClickscoService;

class ReportController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('reports/google', [ReportController::class, 'getGoogleReport']);
        Route::get('reports/google-stats', [ReportController::class, 'getGoogleStatsReport']);
        Route::get('reports/yahoo-ddc', [ReportController::class, 'getYahooDDCReport']);
        Route::get('reports/yahoo-ddc-stats', [ReportController::class, 'getYahooDDCStatsReport']);
        Route::get('reports/yahoo-amg', [ReportController::class, 'getYahooAMGReport']);
        Route::get('reports/yahoo-amg-stats', [ReportController::class, 'getYahooAMGStatsReport']);
        Route::get('reports/bing', [ReportController::class, 'getBingReport']);
        Route::get('reports/bing-stats', [ReportController::class, 'getBingStatsReport']);
        Route::get('reports/programmatic', [ReportController::class, 'getProgrammaticReport']);
        Route::get('reports/programmatic-stats', [ReportController::class, 'getProgrammaticStatsReport']);
        Route::get('reports/media', [ReportController::class, 'getMediaReport']);
        Route::get('reports/google-campaign', [ReportController::class, 'getGoogleCampaignReport']);
        Route::get('reports/taboola', [ReportController::class, 'getTaboolaReport']);
        Route::get('reports/yahoo', [ReportController::class, 'getYahooReport']);
        Route::get('reports/clicksco', [ReportController::class, 'getClickscoReport']);

        Route::post('reports/export-bing', [ReportController::class, 'exportBingReport']);
        Route::post('reports/export-yahoo-ddc', [ReportController::class, 'exportYahooDDCReport']);
        Route::post('reports/export-yahoo-amg', [ReportController::class, 'exportYahooAmgReport']);
        Route::post('reports/export-google', [ReportController::class, 'exportGoogle']);
    }

    public function getGoogleReport(Request $request)
    {
        $search = $request->input('search', null);
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();
        $platform = ReportPlatformEnum::memberByValue($request->input('platform', null));
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'desc');

        $reports = GoogleReport::filter($platform, $search, $from, $to, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return GoogleReportResource::collection($reports);
    }

    public function getGoogleStatsReport(Request $request)
    {
        $search = $request->input('search', null);
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();
        $platform = ReportPlatformEnum::memberByValue($request->input('platform', null));

        $reports = GoogleReport::filter($platform, $search, $from, $to)->get();

        $revenue = $reports->sum(function ($item) {
            return (isset($item->data['net_revenue']) && is_numeric($item->data['net_revenue']))
                ? $item->data['net_revenue']
                : 0;
        });

        $clicks = $reports->sum(function ($item) {
            return (isset($item->data['clicks']) && is_numeric($item->data['clicks']))
                ? $item->data['clicks']
                : 0;
        });

        $impressions = $reports->sum(function ($item) {
            return (isset($item->data['impressions']) && is_numeric($item->data['impressions']))
                ? $item->data['impressions']
                : 0;
        });

        return [
            'revenue' => number_format($revenue, 2),
            'clicks' => number_format($clicks),
            'impressions' => number_format($impressions)
        ];
    }

    public function getYahooAMGReport(Request $request)
    {
        $search = $request->input('search', null);
        $type = YahooReportTypeEnum::memberByValue($request->input('type', null));
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();
        $sort_type = $request->input('sort_type', 'desc');

        $reports = YahooReport::filter($type, $search, $from, $to, $sort_type)
            ->paginate($request->input('per_page', 10));

        return YahooReportResource::collection($reports);
    }

    public function getYahooAMGStatsReport(Request $request)
    {
        $search = $request->input('search', null);
        $type = YahooReportTypeEnum::memberByValue($request->input('type', null));
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $reports = YahooReport::filter($type, $search, $from, $to, 'desc')->get();

        $revenue = $reports->sum(function ($item) {
            return (isset($item->data['ESTIMATED_GROSS_REVENUE']) && is_numeric($item->data['ESTIMATED_GROSS_REVENUE']))
                ? $item->data['ESTIMATED_GROSS_REVENUE']
                : 0;
        });

        $clicks = $reports->sum(function ($item) {
            return (isset($item->data['BIDDED_CLICKS']) && is_numeric($item->data['BIDDED_CLICKS']))
                ? $item->data['BIDDED_CLICKS']
                : 0;
        });

        return [
            'revenue' => number_format($revenue, 2),
            'clicks' => number_format($clicks),
        ];
    }

    public function getYahooDDCReport(Request $request)
    {
        $search = $request->input('search', null);
        $type = YahooDDCReportTypeEnum::memberByValue($request->input('type', null));
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();
        $sort_type = $request->input('sort_type', 'desc');

        $reports = YahooDDCReport::filter($type, $search, $from, $to, $sort_type)
            ->paginate($request->input('per_page', 10));

        return YahooReportResource::collection($reports);
    }

    public function getYahooDDCStatsReport(Request $request)
    {
        $search = $request->input('search', null);
        $type = YahooDDCReportTypeEnum::memberByValue($request->input('type', null));
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $reports = YahooDDCReport::filter($type, $search, $from, $to, 'desc')->get();

        $revenue = $reports->sum(function ($item) {
            return (isset($item->data['revenue']) && is_numeric($item->data['revenue']))
                ? $item->data['revenue']
                : 0;
        });

        $clicks = $reports->sum(function ($item) {
            return (isset($item->data['clicks']) && is_numeric($item->data['clicks']))
                ? $item->data['clicks']
                : 0;
        });

        return [
            'revenue' => number_format($revenue, 2),
            'clicks' => number_format($clicks),
        ];
    }

    public function getBingReport(Request $request)
    {
        $search = $request->input('search', null);
        $type = BingReportTypeEnum::memberByValue($request->input('type', null));
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $reports = BingReport::filter($type, $search, $from, $to)
            ->paginate($request->input('per_page', 10));

        return BingReportResource::collection($reports);
    }

    public function getBingStatsReport(Request $request)
    {
        $search = $request->input('search', null);
        $type = BingReportTypeEnum::memberByValue($request->input('type', null));
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $reports = BingReport::filter($type, $search, $from, $to)->get();

        $revenue = $reports->sum(function ($item) {
            return (isset($item->data['estimatedrevenue']) && is_numeric($item->data['estimatedrevenue']))
                ? $item->data['estimatedrevenue']
                : 0;
        });

        $clicks = $reports->sum(function ($item) {
            return (isset($item->data['clicks']) && is_numeric($item->data['clicks']))
                ? $item->data['clicks']
                : 0;
        });

        $impressions = $reports->sum(function ($item) {
            return (isset($item->data['impressions']) && is_numeric($item->data['impressions']))
                ? $item->data['impressions']
                : 0;
        });

        return [
            'revenue' => number_format($revenue, 2),
            'clicks' => number_format($clicks),
            'impressions' => number_format($impressions)
        ];
    }

    public function getProgrammaticReport(Request $request)
    {
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now()->subDays(7);
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $sort_type = $request->input('sort_type', 'desc');

        $reports = ProgrammaticReport::filter($from, $to, $sort_type)
            ->paginate($request->input('per_page', 10));

        return ProgrammaticReportResource::collection($reports);
    }

    public function getProgrammaticStatsReport(Request $request)
    {
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now()->subDays(7);
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $reports = ProgrammaticReport::filter($from, $to, 'desc')->get();

        $revenue = $reports->sum(function ($item) {
            return (isset($item->data['estimated_revenue']) && is_numeric($item->data['estimated_revenue']))
                ? $item->data['estimated_revenue']
                : 0;
        });

        $impressions = $reports->sum(function ($item) {
            return (isset($item->data['ad_impressions']) && is_numeric($item->data['ad_impressions']))
                ? $item->data['ad_impressions']
                : 0;
        });

        return [
            'revenue' => number_format($revenue, 2),
            'impressions' => number_format($impressions)
        ];
    }

    public function getMediaReport(Request $request)
    {
        $from = $request->input('from');

        return MediaNetService::resolve()
            ->collectionAllData($from);
    }

    public function exportBingReport(ExportBingReportRequest $request)
    {
        return BingReportService::export(
            $request->validated()['from'],
            $request->validated()['to'],
            BingReportTypeEnum::memberByValue($request->validated()['type'])
        );
    }

    public function exportYahooDDCReport(ExportYahooDDCReportRequest $request)
    {
        return YahooReportService::exportDCCReport(
            $request->validated()['from'],
            $request->validated()['to'],
            YahooDDCReportTypeEnum::memberByValue($request->validated()['type'])
        );
    }

    public function exportYahooAmgReport(ExportYahooAmgReportRequest $request)
    {
        return YahooReportService::exportAmgReport(
            $request->validated()['from'],
            $request->validated()['to'],
            YahooReportTypeEnum::memberByValue($request->validated()['type'])
        );
    }

    public function exportGoogle(ExportGoogleReportRequest $request)
    {
        return GoogleReportService::exportGoogleReport(
            $request->validated()['from'],
            $request->validated()['to'],
            ReportPlatformEnum::memberByValue($request->validated()['platform'])
        );
    }

    public function getGoogleCampaignReport(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        
        $gs = new GoogleService1();
        return $gs->getCampaignReport($from, $to);
    }

    public function getTaboolaReport(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        
        return TaboolaService::resolve()
            ->collectionAllData($from, $to);
    }

    public function getYahooReport(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        
        return YahooService::resolve()
            ->collectionAllData($from, $to);
    }

    public function getClickscoReport(Request $request)
    {
        $search = $request->input('search', null);
        $from = $request->has('from') ? Carbon::parse($request->input('from')) : Carbon::now();
        $to = $request->has('to') ? Carbon::parse($request->input('to')) : Carbon::now();

        $reports = ClickscoReport::filter($search, $from, $to)->get();

        return ClickscoReportResource::collection($reports);
    }
}

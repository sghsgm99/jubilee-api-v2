<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetFacebookAnalyticsByStepRequest;
use App\Models\Account;
use App\Models\Analytic;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\Request;

use App\Models\Services\AnalyticService;
use App\Services\FacebookService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Route;

class AnalyticController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('analytics/facebook/{tab}', [AnalyticController::class, 'getFacebookAnalyticsByTab']);
        Route::get('insight/facebook', [AnalyticController::class, 'getFacebookInsightByStep']);
    }

    public function getFacebookAnalyticsByTab(string $tab)
    {
        $analytics = AnalyticService::facebookAnalyticsByTab($tab);
        
        if(isset($analytics['error'])) {
            return ResponseService::clientError("Analytics for {$tab} was not successful.", $analytics);
        }
        
        $collection = collect($analytics);
        return [
            'total' => [
                'spend' => $collection->sum('spend'),
                'clicks' => $collection->sum('clicks'),
                'impressions' => $collection->sum('impressions'),
                'reach' => $collection->sum('reach'),
            ],
            'table' => $analytics
        ];
    }

    public function getFacebookInsightByStep(Request $request)
    {
        $insight = AnalyticService::getFacebookInsightByStep(
            $request->input('step', 'campaign'),
            $request->input('id', '23851274996200519'),
            $request->input('rule', 'visitor')
        );

        return $insight;
    }
}

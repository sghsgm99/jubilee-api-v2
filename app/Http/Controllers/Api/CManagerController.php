<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Models\Services\CManagerService;
use App\Models\Account;

class CManagerController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('cmanagers/buyer', [CManagerController::class, 'getBuyers']);
        Route::get('cmanagers/account', [CManagerController::class, 'getAccounts']);
        Route::get('cmanagers/campaign', [CManagerController::class, 'getCampaigns']);
        Route::get('cmanagers/adset', [CManagerController::class, 'getAdSets']);
        Route::get('cmanagers/ad', [CManagerController::class, 'getAds']);
    }

    public function getBuyers(Request $request)
    {
        $buyers = CManagerService::getMediaBuyers();

        return $buyers;
    }

    public function getAccounts(Request $request)
    {
        $accountList = [
            'data' => []
        ];

        return $accountList;
    }

    public function getCampaigns(Request $request)
    {
        $campaignList = [
            'data' => []
        ];

        return $campaignList;
    }

    public function getAdSets(Request $request)
    {
        $adSetList = [
            'data' => []
        ];

        return $adSetList;
    }

    public function getAds(Request $request)
    {
        $adList = [
            'data' => []
        ];

        return $adList;
    }
}

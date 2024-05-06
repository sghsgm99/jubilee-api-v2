<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Services\GoogleKeywordService;
use App\Models\Services\AdTemplateService;

class AdTemplateController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('adtemplates/keyword-high', [AdTemplateController::class, 'getKeywordHigh']);
        Route::get('adtemplates/keyword-low', [AdTemplateController::class, 'getKeywordLow']);
        Route::get('adtemplates/get-campaign-names', [AdTemplateController::class, 'getCampaignNames']);
        Route::post('adtemplates/submit-campaigns', [AdTemplateController::class, 'createCampaignAds']);
    }

    public function getKeywordHigh(Request $request)
    {
        $kw = $request->input('keyword');
        $lvalue = $request->input('lvalue');

        return AdTemplateService::searchKeywordHigh($kw, $lvalue);
    }

    public function getCampaignNames(Request $request)
    {
        $kw = $request->input('keyword');
        $lvalue = $request->input('lvalue');

        $gs = new GoogleKeywordService();
        return $gs->getCampaignNames($kw, $lvalue);
    }

    public function getKeywordLow(Request $request)
    {
        $kw = $request->input('keyword');
        $lvalue = $request->input('lvalue');
        $currentPage = $request->input('page');
        $perPage = $request->input('per_page');

        $gs = new GoogleKeywordService();
        return $gs->getKeywordLow($currentPage, $perPage, $kw, $lvalue);
    }

    public function createCampaignAds(Request $request)
    {
        $kws = $request->input('kws');
        $intls = $request->input('intls');
        $budget = $request->input('budget');
        $keyword = $request->input('keyword');
        $net_search = $request->input('net_search');
        $net_display = $request->input('net_display');

        $gn_options = $request->input('gn_options');
        $bid = $request->input('bid');

        $ek_list = $request->input('ek_text');
        $pk_list = $request->input('pk_text');
        $bk_list = $request->input('bk_text');

        $finalURL = $request->input('finalurl');
        $path1 = $request->input('path1');
        $path2 = $request->input('path2');
        $headlines = $request->input('headlines');
        $descriptions = $request->input('descriptions');

        $gs = new GoogleKeywordService();
        return $gs->createCampaignAds($kws, $intls, $budget, $keyword, $net_search, $net_display, 
                        $gn_options, $bid, 
                        $ek_list, $pk_list, $bk_list,
                        $finalURL, $path1, $path2, $headlines, $descriptions);
    }
}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\KeywordSpinning;
use App\Services\ResponseService;
use App\Models\Services\KeywordSpinningService;
use App\Http\Resources\KeywordSpinningResource;
use App\Models\Enums\SerpCategoryEnum;

class KeywordSpinningController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('keywordspinning/yahoo', [KeywordSpinningController::class, 'getCollectionYahoo']);
        Route::get('keywordspinning/seed-yahoo', [KeywordSpinningController::class, 'getSeedKeywordYahoo']);
        Route::get('keywordspinning/bing', [KeywordSpinningController::class, 'getCollectionBing']);
        Route::get('keywordspinning/seed-bing', [KeywordSpinningController::class, 'getSeedKeywordBing']);
    }

    public function getCollectionYahoo(Request $request)
    {
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $kslists = KeywordSpinning::search($sort, $sort_type, SerpCategoryEnum::YAHOO)
            ->paginate($request->input('per_page', 10));

        return KeywordSpinningResource::collection($kslists);
    }

    public function getSeedKeywordYahoo(Request $request)
    {
        $query = $request->input('keyword');

        $activelist = KeywordSpinningService::getYahooActiveList($query);

        return $activelist;
    }

    public function getCollectionBing(Request $request)
    {
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $kslists = KeywordSpinning::search($sort, $sort_type, SerpCategoryEnum::BING)
            ->paginate($request->input('per_page', 10));

        return KeywordSpinningResource::collection($kslists);
    }

    public function getSeedKeywordBing(Request $request)
    {
        $query = $request->input('keyword');

        $ksservice = new KeywordSpinningService();

        return $ksservice->getBingActiveList($query);
    }
}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use App\Models\Services\AdPartnerService;
use App\Models\Services\SiteLogService;
use App\Models\SiteLog;
use App\Http\Resources\SiteLogResource;

class AdPartnerController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('adpartners/submit-yahoo', [AdPartnerController::class, 'setYahoo']);
        Route::get('adpartners/fetch-yahoo', [AdPartnerController::class, 'getYahoo']);
        Route::post('adpartners/submit-google', [AdPartnerController::class, 'setGoogle']);
        Route::get('adpartners/fetch-google', [AdPartnerController::class, 'getGoogle']);
        Route::post('adpartners/submit-bing', [AdPartnerController::class, 'setBing']);
        Route::get('adpartners/fetch-bing', [AdPartnerController::class, 'getBing']);
        Route::get('adpartners/getAdClick', [AdPartnerController::class, 'getAdClick']);
    }

    public function getYahoo()
    {
        return ResponseService::success('Success', AdPartnerService::getYahoo());
    }

    public function setYahoo(Request $request)
    {
        $token_key = $request->input('token_key');
        $ocode = $request->input('ocode');
        $serve_url = $request->input('serve_url');

        AdPartnerService::updateYahoo($token_key, $ocode, $serve_url);
        
        return ResponseService::success('Success');
    }

    public function getGoogle()
    {
        return ResponseService::success('Success', AdPartnerService::getGoogle());
    }

    public function setGoogle(Request $request)
    {
        $ocode = $request->input('ocode');
        $serve_url = $request->input('serve_url');

        AdPartnerService::updateGoogle($ocode, $serve_url);
        
        return ResponseService::success('Success');
    }

    public function getBing()
    {
        return ResponseService::success('Success', AdPartnerService::getBing());
    }

    public function setBing(Request $request)
    {
        $ocode = $request->input('ocode');
        $serve_url = $request->input('serve_url');

        AdPartnerService::updateBing($ocode, $serve_url);
        
        return ResponseService::success('Success');
    }
    
    public function getAdClick(Request $request)
    {
        $search = $request->input('search', null);

        $logs = SiteLog::search($search)
            ->paginate($request->input('per_page', 10));

        return SiteLogResource::collection($logs);
    }
}

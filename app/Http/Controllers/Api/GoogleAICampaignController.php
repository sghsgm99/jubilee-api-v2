<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\GoogleAICampaign;
use App\Models\GoogleCampaignLog;
use App\Models\Services\GoogleAICampaignModelService;
use App\Http\Resources\GoogleAICampaignResource;
use App\Http\Resources\GoogleCampaignLogResource;
use App\Services\ResponseService;

class GoogleAICampaignController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-ai-campaigns', [GoogleAICampaignController::class, 'getCollection']);
        Route::post('google-ai-campaigns', [GoogleAICampaignController::class, 'create']);
        Route::get('google-ai-campaigns/updateStatus/{googleAICampaign}', [GoogleAICampaignController::class, 'updateStatus']);
        Route::get('google-ai-campaigns/logs', [GoogleAICampaignController::class, 'getLogCollection']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');
        
        $campaigns = GoogleAICampaign::search(
            $search,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleAICampaignResource::collection($campaigns);
    }

    public function create(Request $request)
    {
        $campaign = GoogleAICampaignModelService::create(
            $request->input('title'),
            $request->input('base_url'),
            $request->input('budget'),
            $request->input('bid'),
            $request->input('customer_id')
        );

        if (isset($campaign['error'])) {
            return ResponseService::serverError('AI Campaign was not created.');
        }

        return ResponseService::successCreate('AI Campaign was created.', new GoogleAICampaignResource($campaign));
    }

    public function updateStatus(GoogleAICampaign $googleAICampaign, Request $request)
    {
        $campaign = $googleAICampaign->Service()->updateStatus(
            $request->input('status')
        );

        if (isset($campaign['error'])) {
            return ResponseService::serverError('AI Campaign was not updated.');
        }

        return ResponseService::successCreate('AI Campaign was updated.', new GoogleAICampaignResource($campaign));
    }

    public function getLogCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');
        
        $campaigns = GoogleCampaignLog::search(
            $search,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleCampaignLogResource::collection($campaigns);
    }
}

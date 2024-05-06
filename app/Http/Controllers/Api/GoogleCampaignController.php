<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\GoogleCampaign;
use App\Models\Services\GoogleCampaignModelService;
use App\Http\Resources\GoogleCampaignResource;
use App\Services\ResponseService;

class GoogleCampaignController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-campaigns', [GoogleCampaignController::class, 'getCollection']);
        Route::post('google-campaigns', [GoogleCampaignController::class, 'create']);
        Route::put('google-campaigns/{googleCampaign}', [GoogleCampaignController::class, 'update']);
        Route::get('google-campaigns/{googleCampaign}', [GoogleCampaignController::class, 'getSingle']);
        Route::get('google-campaigns/updateStatus/{googleCampaign}', [GoogleCampaignController::class, 'updateStatus']);
        Route::delete('google-campaigns/{googleCampaign}', [GoogleCampaignController::class, 'delete']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $google_account = $request->input('google_account', null);
        $customer_id = $request->input('customer_id', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');
        
        $campaigns = GoogleCampaign::search(
            $search,
            $google_account,
            $customer_id,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleCampaignResource::collection($campaigns);
    }

    public function create(Request $request)
    {
        $campaign = GoogleCampaignModelService::create(
            $request->input('title'),
            $request->input('description', null),
            $request->input('customer_id'),
            $request->input('budget'),
            $request->input('location'),
            $request->input('type'),
            $request->input('status'),
            $request->input('data', null)
        );

        if (isset($campaign['error'])) {
            return ResponseService::serverError('Campaign was not created.');
        }

        return ResponseService::successCreate('Campaign was created.', new GoogleCampaignResource($campaign));
    }
    
    public function update(GoogleCampaign $googleCampaign, Request $request)
    {
        $campaign = $googleCampaign->Service()->update(
            $request->input('title'),
            $request->input('description', null),
            $request->input('budget'),
            $request->input('location'),
            $request->input('status'),
            $request->input('data', null)
        );

        if (isset($campaign['error'])) {
            return ResponseService::serverError('Campaign was not updated.');
        }

        return ResponseService::successCreate('Campaign was updated.', new GoogleCampaignResource($campaign));
    }

    public function getSingle(GoogleCampaign $googleCampaign)
    {
        return new GoogleCampaignResource($googleCampaign);
    }

    public function updateStatus(GoogleCampaign $googleCampaign, Request $request)
    {
        $campaign = $googleCampaign->Service()->updateStatus(
            $request->input('status')
        );

        if (isset($campaign['error'])) {
            return ResponseService::serverError('Campaign was not updated.');
        }

        return ResponseService::successCreate('Campaign was updated.', new GoogleCampaignResource($campaign));
    }

    public function delete(GoogleCampaign $googleCampaign)
    {
        $googleCampaign->Service()->delete();

        return ResponseService::successCreate('Campaign was deleted successfully.');
    }
}

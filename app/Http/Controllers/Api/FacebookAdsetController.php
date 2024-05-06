<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFacebookAdsetRequest;
use App\Http\Requests\UpdateFacebookAdsetRequest;
use App\Http\Resources\FacebookAdsetResource;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;
use App\Models\Services\FacebookAdsetModelService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FacebookAdsetController extends Controller
{
    public static function apiRoutes()
    {
        Route::delete('facebook-adsets/{facebookAdset}', [FacebookAdsetController::class, 'delete']);
        Route::post('facebook-adsets/multiple-create/test', [FacebookAdsetController::class, 'multipleCreateTest']);
        Route::post('facebook-adsets', [FacebookAdsetController::class, 'create']);
        Route::put('facebook-adsets/{facebookAdset}', [FacebookAdsetController::class, 'update']);
        Route::get('facebook-adsets/{facebookAdset}/duplicate', [FacebookAdsetController::class, 'duplicate']);
        Route::get('facebook-adsets/{facebookAdset}/toggle-status', [FacebookAdsetController::class, 'toggleStatus']);
        Route::get('facebook-adsets/{facebookAdset}', [FacebookAdsetController::class, 'getSingle']);
        Route::get('facebook-adsets', [FacebookAdsetController::class, 'getCollection']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = CampaignInAppStatusEnum::memberByValue($request->input('status', null));
        $campaign_id = $request->input('campaign', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $adset = FacebookAdset::search(
            $search,
            $status,
            $campaign_id,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return FacebookAdsetResource::collection($adset);
    }

    public function getSingle(FacebookAdset $facebookAdset)
    {
        return new FacebookAdsetResource($facebookAdset);
    }

    public function create(CreateFacebookAdsetRequest $request)
    {
        $adsets = FacebookAdsetModelService::multipleCreate(
            $request
        );
        
        if (isset($adsets['error'])) {
            return ResponseService::clientError('Adset was not created.', $adsets);
        }

        return ResponseService::successCreate('Adset was created.', FacebookAdsetResource::collection($adsets));
    }

    public function update(FacebookAdset $facebookAdset, UpdateFacebookAdsetRequest $request)
    {
        $adset = $facebookAdset->Service()->update(
            $request->validated()['title'],
            $request->validated()['adset'],
            CampaignInAppStatusEnum::memberByValue($request->validated()['status'])
        );

        if (isset($adset['error'])) {
            return ResponseService::clientError('Adset was not updated.', $adset);
        }

        return ResponseService::successCreate('Adset was updated.', new FacebookAdsetResource($adset));
    }

    public function duplicate(FacebookAdset $facebookAdset, Request $request)
    {
        $adset = $facebookAdset->Service()->duplicate(null,$request->input('deep', false));

        if (isset($adset['error'])) {
            return ResponseService::serverError($adset['message']);
        }
        return ResponseService::successCreate(
            'Adset duplicated successfully',
            new facebookAdsetResource($adset)
        );
    }

    public function toggleStatus(FacebookAdset $facebookAdset)
    {
        $adset = $facebookAdset->Service()->toggleStatus();

        if (isset($adset['error'])) {
            return ResponseService::serverError($adset['message']);
        }
        return ResponseService::successCreate(
            "Adset is now {$adset->fb_status->value}",
            new FacebookAdsetResource($adset)
        );
    }

    public function delete(FacebookAdset $facebookAdset)
    {
        $delete = $facebookAdset->Service()->delete();

        if (isset($delete['error'])) {
            return ResponseService::clientError(
                'Adset was not deleted.',
                $delete
            );
        }

        return ResponseService::successCreate('Adset was deleted successfully.');
    }

    public function multipleCreateTest(Request $request)
    {
        return FacebookAdsetModelService::createMultipleTest(
            FacebookCampaign::findOrFail($request->campaign_id),
            $request->input('copies', 0)
        );
    }
}

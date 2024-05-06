<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CreateStandaloneCampaignRequest;
use App\Http\Requests\UpdateStandaloneCampaignRequest;
use App\Models\Channel;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Site;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Services\CampaignService;
use App\Models\Enums\CampaignStatusEnum;
use App\Http\Requests\CreateCampaignRequest;
use App\Http\Requests\DeleteMultipleCampaignRequest;
use App\Http\Requests\FacebookGeneratePreviewRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Image;
use App\Services\ResponseService;

use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('campaigns', [CampaignController::class, 'create']);
        Route::post('campaigns/standalone', [CampaignController::class, 'createStandalone']);
        Route::post('campaigns/{campaign}/update-facebook-status', [CampaignController::class, 'updateFacebookStatus']);
        Route::post('campaigns/{campaign}/standalone', [CampaignController::class, 'updateStandalone']);
        Route::put('campaigns/{campaign}', [CampaignController::class, 'update']);
        Route::delete('campaigns/delete', [CampaignController::class, 'deleteMultiple']);
        Route::delete('campaigns/{campaign}', [CampaignController::class, 'delete']);
        Route::get('campaigns/{campaign}/toggle-status', [CampaignController::class, 'toggleFacebookStatus']);
        Route::get('campaigns/{campaign}', [CampaignController::class, 'get']);
        Route::get('campaigns', [CampaignController::class, 'getCollection']);

        // Insights
        Route::get('campaigns/{campaign}/facebook-insights', [CampaignController::class, 'getSingleFacebookInsight']);

        // standalone generate preview
        Route::post('campaigns/standalone/generate-preview', [CampaignController::class, 'generatePreview']);

        // standalone test campaign endpoint
        Route::post('campaigns/{campaign}/test-submission', [CampaignController::class, 'createTestSubmission']);

        // duplicate campaign
        Route::get('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicateCampaign']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = CampaignStatusEnum::memberByValue($request->input('status', null));
        $tags = explode(',', $request->input('tag_ids', ''));
        $channel_id = $request->input('channel', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $campaign = Campaign::search($search, $status, $tags, $channel_id, $sort, $sort_type)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return CampaignResource::collection($campaign);
    }

    public function get(Campaign $campaign)
    {
        $camp = $campaign->Service()->getCampagin($campaign);

        if (isset($camp['error'])) {
            return ResponseService::serverError($camp['message']);
        }

        return $camp;
    }

    public function create(CreateCampaignRequest $request)
    {
        $campaign = CampaignService::create(
            $request->validated()['title'],
            $request->validated()['description'],
            $request->validated()['channel_api_preferences'] ?? [],
            $request->validated()['channel_id'],
            $request->validated()['article_id'],
            $request->validated()['site_id'],
            $request->validated()['data'],
            CampaignStatusEnum::memberByValue($request->validated()['status']),
            $request->validated()['ad_account'] ?? null
        );

        if ($campaign['status'] == 'error') {
            return ResponseService::clientError('Campaign was not created.', $campaign);
        }

        return ResponseService::successCreate('Campaign was created.', new CampaignResource($campaign));
    }

    public function createStandalone(CreateStandaloneCampaignRequest $request)
    {
        $campaign = CampaignService::createStandalone(
            $request->validated()['title'],
            $request->validated()['description'],
            $request->validated()['channel_api_preferences'] ?? [],
            Channel::findOrFail($request->validated()['channel_id']),
            Site::findOrFail($request->validated()['site_id']),
            $request->validated()['data'],
            CampaignStatusEnum::memberByValue($request->validated()['status']),
            $request->validated()['primary_text'],
            $request->validated()['headline'],
            FacebookCallToActionEnum::memberByValue($request->validated()['call_to_action']),
            $request->validated()['ad_image'] ?? null,
            $request->validated()['ad_description'] ?? null,
            $request->validated()['display_link'] ?? null,
            $request->validated()['ad_account'] ?? null
        );

        if (isset($campaign['status']) && $campaign['status'] === 'error') {
            return ResponseService::clientError('Campaign was not created.', $campaign);
        }

        return ResponseService::successCreate('Campaign was created.', new CampaignResource($campaign));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $camp = $campaign->Service()->update(
            $request->validated()['title'],
            $request->validated()['description'],
            $request->validated()['channel_id'],
            $request->validated()['article_id'],
            $request->validated()['site_id'],
            $request->validated()['data'],
            CampaignStatusEnum::memberByValue($request->validated()['status']),
            $request->validated()['ad_account'] ?? null
        );

        if (isset($camp['status']) && $camp['status'] == 'error') {
            return ResponseService::clientError('Campaign was not updated.', $camp);
        }

        return ResponseService::successCreate('Campaign was updated.', new CampaignResource($camp));
    }

    public function updateStandalone(UpdateStandaloneCampaignRequest $request, Campaign $campaign)
    {
        $camp = $campaign->Service()->updateStandalone(
            $request->validated()['title'],
            $request->validated()['description'],
            Channel::findOrFail($request->validated()['channel_id']),
            Site::findOrFail($request->validated()['site_id']),
            $request->validated()['data'],
            CampaignStatusEnum::memberByValue($request->validated()['status']),
            $request->validated()['primary_text'],
            $request->validated()['headline'],
            FacebookCallToActionEnum::memberByValue($request->validated()['call_to_action']),
            $request->validated()['ad_image'] ?? null,
            $request->validated()['ad_description'] ?? null,
            $request->validated()['display_link'] ?? null,
            $request->validated()['ad_account'] ?? null
        );

        if (isset($camp['status']) && $camp['status'] === 'error') {
            return ResponseService::clientError('Campaign was not updated.', $camp);
        }

        return ResponseService::successCreate('Campaign was updated.', new CampaignResource($camp));
    }

    public function delete(Campaign $campaign)
    {
        $camp = $campaign->Service()->deleteCampaign();

        if (is_array($camp) && $camp['status'] == 'error') {
            return ResponseService::clientError('Campaign was not deleted.', $camp);
        }

        return ResponseService::successCreate('Campaign was deleted successfully.');
    }

    public function deleteMultiple(DeleteMultipleCampaignRequest $request)
    {
        return CampaignService::BulkDelete($request->validated()['ids']);
    }

    public function updateFacebookStatus(Request $request, Campaign $campaign)
    {
        $camp = $campaign->Service()->updateFacebookStatus(
            FacebookCampaignStatusEnum::memberByValue($request->facebook_status)
        );
        if ($camp['status'] == 'error') {
            return ResponseService::serverError($camp['message']);
        }

        return ResponseService::successCreate('Campaign facebook status was updated.', new CampaignResource($camp));
    }

    public function toggleFacebookStatus(Campaign $campaign)
    {
        $camp = $campaign->Service()->toggleFacebookStatus();

        if ($camp['status'] == 'error') {
            return ResponseService::serverError($camp['message']);
        }

        return ResponseService::successCreate('Campaign facebook status was updated.', new CampaignResource($camp));
    }

    public function getSingleFacebookInsight(Campaign $campaign)
    {
        $camp = $campaign->Service()->getSingleFacebookCampaignInsight();

        if (isset($camp['error'])) {
            return ResponseService::serverError($camp['message']);
        }

        return $camp;
    }

    public function generatePreview(FacebookGeneratePreviewRequest $request)
    {
        return CampaignService::generatePreview(
            Channel::findOrFail($request->validated()['channel_id']),
            $request->validated()['primary_text'],
            $request->validated()['headline'],
            FacebookCallToActionEnum::memberByValue($request->validated()['call_to_action']),
            Image::findOrFail($request->validated()['ad_image'])->path,
            $request->validated()['ad_description'],
            $request->validated()['display_link'],
            $request->validated()['ad_account']
        );
    }

    public function duplicateCampaign(Campaign $campaign)
    {
        $camp = $campaign->Service()->duplicateCampaign();

        if(isset($camp['error'])) {
            return ResponseService::clientError($camp['message'], $camp);
        }

        return ResponseService::successCreate('Campaign duplicated successfully.', new CampaignResource($camp));
    }

    public function createTestSubmission(Campaign $campaign, Request $request)
    {
        return $campaign->Service()->createTestSubmission($request);
    }
}

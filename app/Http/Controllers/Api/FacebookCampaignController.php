<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachRuleAutomationToCampaignRequest;
use App\Http\Requests\CreateFacebookCampaignRequest;
use App\Http\Requests\UpdateFacebookCampaignRequest;
use App\Http\Resources\FacebookCampaignResource;
use App\Models\Channel;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\FacebookCampaign;
use App\Models\FacebookRuleAutomation;
use App\Models\Services\FacebookCampaignModelService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FacebookCampaignController extends Controller
{
    public static function apiRoutes()
    {
        Route::delete('facebook-campaigns/{facebookCampaign}', [FacebookCampaignController::class, 'delete']);
        Route::post('facebook-campaigns', [FacebookCampaignController::class, 'create']);
        Route::post('facebook-campaigns/{facebookCampaign}/attach', [FacebookCampaignController::class, 'attachRuleAutomation']);
        Route::put('facebook-campaigns/{facebookCampaign}', [FacebookCampaignController::class, 'update']);
        Route::get('facebook-campaigns/{facebookCampaign}/duplicate', [FacebookCampaignController::class, 'duplicate']);
        Route::get('facebook-campaigns/{facebookCampaign}/toggle-status', [FacebookCampaignController::class, 'toggleStatus']);
        Route::get('facebook-campaigns/{facebookCampaign}', [FacebookCampaignController::class, 'getSingle']);
        Route::get('facebook-campaigns', [FacebookCampaignController::class, 'getCollection']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = CampaignInAppStatusEnum::memberByValue($request->input('status', null));
        $tags = explode(',', $request->input('tag_ids', ''));
        $channel_id = $request->input('channel', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');
        $ad_account_id = $request->input('ad_account_id', null);

        $campaigns = FacebookCampaign::search(
            $search,
            $status,
            $tags,
            $channel_id,
            $sort,
            $sort_type,
            $ad_account_id
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return FacebookCampaignResource::collection($campaigns);
    }

    public function getSingle(FacebookCampaign $facebookCampaign)
    {
        return new FacebookCampaignResource($facebookCampaign);
    }

    public function create(CreateFacebookCampaignRequest $request)
    {
        $campaign = FacebookCampaignModelService::create(
            Channel::findOrFail($request->validated()['channel_id']),
            $request->validated()['ad_account_id'],
            $request->validated()['title'],
            $request->validated()['description'],
            FacebookCampaignObjectiveEnum::memberByValue($request->validated()['objective']),
            CampaignInAppStatusEnum::memberByValue($request->validated()['status'])
        );

        if (isset($campaign['error'])) {
            return ResponseService::clientError('Campaign was not created.', $campaign);
        }

        return ResponseService::successCreate('Campaign was created.', new FacebookCampaignResource($campaign));
    }

    public function attachRuleAutomation(AttachRuleAutomationToCampaignRequest $request, FacebookCampaign $facebookCampaign)
    {
        $campaign = $facebookCampaign->Service()->attachRuleAutomation(
            FacebookRuleAutomation::find($request->validated()['rule_automation_id'])
        );

        return ResponseService::successCreate(
            'Rule automation was attached to the campaign.',
            new FacebookCampaignResource($campaign)
        );
    }

    public function update(FacebookCampaign $facebookCampaign, UpdateFacebookCampaignRequest $request)
    {
        $campaign = $facebookCampaign->Service()->update(
            $request->validated()['title'],
            $request->validated()['description'],
            CampaignInAppStatusEnum::memberByValue($request->validated()['status'])
        );

        if (isset($campaign['error'])) {
            return ResponseService::clientError('Campaign was not updated.', $campaign);
        }

        return ResponseService::successCreate('Campaign was updated.', new FacebookCampaignResource($campaign));
    }

    public function duplicate(FacebookCampaign $facebookCampaign, Request $request)
    {
        $campaign = $facebookCampaign->Service()->duplicate($request->input('deep', false));

        if (isset($campaign['error'])) {
            return ResponseService::serverError($campaign['message']);
        }
        return ResponseService::successCreate(
            'Campaign duplicated successfully',
            new FacebookCampaignResource($campaign)
        );
    }

    public function toggleStatus(FacebookCampaign $facebookCampaign)
    {
        $campaign = $facebookCampaign->Service()->toggleStatus();

        if (isset($campaign['error'])) {
            return ResponseService::serverError($campaign['message']);
        }
        return ResponseService::successCreate(
            "Campaign is now {$campaign->fb_status->value}",
            new FacebookCampaignResource($campaign)
        );
    }

    public function delete(FacebookCampaign $facebookCampaign)
    {
        $facebookCampaign->Service()->delete();

        return ResponseService::successCreate('Campaign was deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UploadChannelImage;
use App\Http\Resources\ImageResource;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddUserToChannelRequest;
use App\Http\Resources\ChannelResource;
use App\Models\Services\ChannelService;
use App\Models\Channel;
use App\Models\User;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Http\Requests\CreateChannelRequest;
use App\Http\Requests\CreateFacebookAdAccountNameRequest;
use App\Http\Requests\UpdateChannelRequest;
use App\Http\Requests\CreateFacebookAdRequest;
use App\Http\Requests\CreateFacebookGenerateAdAccountNameRequest;
use App\Http\Requests\DeleteMultipleChannelRequest;
use App\Http\Requests\UpdateFacebookAdAccountNameRequest;
use App\Http\Resources\FacebookAdAccountResource;
use App\Models\ChannelFacebook;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\FacebookAdAccountStatusEnum;
use App\Models\Enums\FacebookAdSetBidStrategyEnum;
use App\Models\Enums\FacebookAdSetBillingEventEnum;
use App\Models\Enums\FacebookBudgetTypeEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\FacebookTimezoneEnum;
use App\Models\Enums\FacebookVerticalEnum;
use App\Models\FacebookAdAccount;
use App\Models\Services\ChannelFacebookService;
use App\Services\FacebookService;
use App\Services\ResponseService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

class ChannelController extends Controller
{
    public static function apiRoutes()
    {
        // facebook api campaign endpoints
        Route::post('channels/{channel}/facebook-ads/campaign', [ChannelController::class, 'createCampaign']);
        Route::put('channels/{channel}/facebook-ads/campaign/{campaign_id}', [ChannelController::class, 'updateCampaign']);
        Route::delete('channels/{channel}/facebook-ads/delete-campaign/{campaign_id}', [ChannelController::class, 'deleteCampaign']);
        Route::get('channels/{channel}/facebook-ads/get-campaign/{campaign_id}', [ChannelController::class, 'getSingleCampaign']);
        Route::get('channels/{channel}/facebook-ads/get-campaigns', [ChannelController::class, 'getCampaigns']);

        // facebook api ad set endpoints
        Route::post('channels/{channel}/facebook-ads/adset/{campaign_id}', [ChannelController::class, 'createAdSet']);
        Route::put('channels/{channel}/facebook-ads/adset/{adset_id}', [ChannelController::class, 'updateAdSet']);
        Route::delete('channels/{channel}/facebook-ads/adset/{adset_id}', [ChannelController::class, 'deleteAdSet']);
        Route::get('channels/{channel}/facebook-ads/adsets', [ChannelController::class, 'getAdSets']);
        Route::get('channels/{channel}/facebook-ads/adset/{adset_id}', [ChannelController::class, 'getSingleAdSet']);

        // facebook api ad endpoints
        Route::get('channels/{channel}/facebook-ads/ads', [ChannelController::class, 'getAds']);
        Route::post('channels/{channel}/facebook-ads/ads', [ChannelController::class, 'createAd']);
        Route::delete('channels/{channel}/facebook-ads/ad/{ad_id}', [ChannelController::class, 'deleteAd']);
        Route::get('channels/{channel}/facebook-ads/ad/{ad_id}', [ChannelController::class, 'getSingleAd']);

        // facebook api ad set targeting search endpoints
        Route::get('channels/{channel}/facebook-ads/adset/target-search/location', [ChannelController::class, 'targetSearchLocationAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/locale', [ChannelController::class, 'targetSearchLocaleAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/interest', [ChannelController::class, 'targetSearchInterestAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/behavior', [ChannelController::class, 'targetSearchBehaviorAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/education-school', [ChannelController::class, 'targetSearchEducationSchoolAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/education-major', [ChannelController::class, 'targetSearchEducationMajorAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/work-employer', [ChannelController::class, 'targetSearchWorkEmployerAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/job-title', [ChannelController::class, 'targetSearchJobTitleAdSet']);
        Route::get('channels/{channel}/facebook-ads/adset/target-search/category', [ChannelController::class, 'targetSearchCategoryAdSet']);

        // facebook api insights
        Route::get('channels/{channel}/facebook-insights', [ChannelController::class, 'getFacebookInsight']);
        Route::get('channels/{channel}/facebook-detailed-insights', [ChannelController::class, 'getFacebookDetailedInsight']);

        Route::get('channels/{channel}/facebook/payment-method', [ChannelController::class, 'createChannelFacebookPaymentMethod']);
        Route::get('channels/{channel}/facebook/get-funding-source', [ChannelController::class, 'createChannelFacebookFundingSource']);

        
        // create child bm ad account multiple
        Route::post('channels/{channel}/facebook/ad-account', [ChannelController::class, 'createChannelFacebookAdAccount']);
        // generate child bm ad account single
        Route::get('channels/{channel}/facebook/ad-account', [ChannelController::class, 'createChannelFacebookAdAccountSingle']);
        // view child bm ad accounts
        Route::get('channels/{channel}/facebook/get/ad-accounts', [ChannelController::class, 'getChannelFacebookAdAccount']);
        // update child bm ad account
        Route::put('channels/{channel}/facebook/ad-account', [ChannelController::class, 'updateChannelFacebookAdAccount']);


        // generate child bm access tokent
        Route::get('channels/facebook/access-token/{id}', [ChannelController::class, 'createChannelFacebookAccessToken']);

        Route::get('channels/facebook/get-business-managers', [ChannelController::class, 'getBusinessManagers']);


        // update facebook ad accounts to db
        Route::post('channels/facebook/update/ad-accounts', [ChannelController::class, 'updateFacebookAdAccounts']);
        // get facebook ad accounts from db
        Route::get('channels/facebook/get/ad-accounts', [ChannelController::class, 'getFacebookAdAccounts']);
        // get facebook ad accounts from parent bm
        Route::get('channels/facebook/get/parent-bm/ad-accounts', [ChannelController::class, 'getParentBMAdAccounts']);
        // get facebook ad accounts from child bm


        // get facebook pages
        Route::get('channels/facebook/get/pages', [ChannelController::class, 'getPages']);
        // remove child bm id
        // Route::delete('channels/delete/child-bm/{child_id}', [ChannelController::class, 'deleteChildId']);
        // update parent bm
        Route::get('channels/update/parent-bm', [ChannelController::class, 'updateParentBM']);
        // get parent business users
        Route::get('channels/parent-bm/business-users', [ChannelController::class, 'getParentBusinessUsers']);

        // add users to child bm
        Route::post('child-bm/{channel}/add-users', [ChannelController::class, 'inviteUsersToChild']);
        // get users from child bm
        Route::get('child-bm/{channel}/users', [ChannelController::class, 'getUsersFromChild']);
        // delete users from child bm
        Route::delete('child-bm/{channel}/delete-users/{user_id}', [ChannelController::class, 'deleteUserFromChild']);

        // get pages form child bm
        Route::get('child-bm/{channel}/pages', [ChannelController::class, 'getPagesFromChild']);
        // add page to child bm
        Route::post('child-bm/{channel}/pages', [ChannelController::class, 'addPageToChild']);
        // delete page from child bm
        Route::delete('child-bm/{channel}/page/{page}', [ChannelController::class, 'deletePageFromChild']);
        Route::get('child-bm/{channel}/user-page/{user_id}', [ChannelController::class, 'assignUsersToPage']);

        // Generate LOC for child bm
        Route::get('child-bm/{channel}/loc', [ChannelController::class, 'generateLocForChild']);
        // View LOC for child bm
        Route::get('child-bm/{channel}/view-loc', [ChannelController::class, 'getLocForChild']);



        Route::post('channels', [ChannelController::class, 'create']);
        Route::post('channels/{channel}/upload-image', [ChannelController::class, 'uploadImage']);
        Route::put('channels/{channel}/featured-image/{image_id}', [ChannelController::class, 'setFeaturedImage']);
        Route::put('channels/{channel}', [ChannelController::class, 'update']);
        Route::delete('channels/delete', [ChannelController::class, 'deleteMultiple']);
        Route::delete('channels/{channel}/image/{id}', [ChannelController::class, 'deleteImage']);
        Route::delete('channels/{channel}', [ChannelController::class, 'delete']);
        Route::get('channels/ad-connect/{channel}', [ChannelController::class, 'adConnect']);
        Route::get('channels/{channel}', [ChannelController::class, 'get']);
        Route::get('channels', [ChannelController::class, 'getCollection']);
    }

    public function createChannelFacebookAdAccount(Channel $channel, CreateFacebookAdAccountNameRequest $request)
    {
        if (!isset($channel->channelFacebook)) {
            return ResponseService::serverError('Channel is not a Facebook Platform');
        }
        $cf = $channel->channelFacebook->service()->createAdAccount(
                $channel,
                $request->validated()['name']
        );

        if (isset($cf['error'])) {
            return ResponseService::clientError($cf['message'], $cf);
        }

        return ResponseService::success('Success', $cf);
    }

    public function getChannelFacebookAdAccount(Channel $channel, Request $request)
    {
        $status = FacebookAdAccountStatusEnum::memberByValue($request->input('account_status', null));
        $act = ChannelFacebookService::getChildBMAdAccounts($channel, $status);

        
        if (isset($act['error'])) {
            return ResponseService::clientError($act['message']);
        }
        
        return ResponseService::success('Success', FacebookAdAccountResource::collection($act));
    }

    public function updateChannelFacebookAdAccount(Channel $channel, UpdateFacebookAdAccountNameRequest $request)
    {
        if (!isset($channel->channelFacebook)) {
            return ResponseService::serverError('Channel is not a Facebook Platform');
        }
        $cf = $channel->channelFacebook->service()->updateAdAccount(
                $channel,
                $request->validated()['name'],
                $request->validated()['account_id'],
        );

        if (isset($cf['error'])) {
            return ResponseService::clientError($cf['message'], $cf);
        }

        return ResponseService::success('Success', $cf);
    }

    public function createChannelFacebookAdAccountSingle(Channel $channel)
    {
        $cf = $channel->channelFacebook->service()->createAdAccountSingle(
                $channel
        );

        if (isset($cf['error'])) {
            return ResponseService::clientError($cf['message'], $cf);
        }

        return ResponseService::success('Success', $cf);
    }

    public function createChannelFacebookAccessToken(string $id)
    {
        $cf = ChannelFacebookService::createAccessToken($id);

        if (isset($cf['error'])) {
            return ResponseService::clientError($cf['message'], $cf);
        }

        return ResponseService::success('Success', $cf);
    }

    public function createChannelFacebookPaymentMethod(Channel $channel)
    {
        if (!isset($channel->channelFacebook)) {
            return ResponseService::serverError('Channel is not a Facebook Platform');
        }
        $cf = $channel->channelFacebook->service()->paymentMethod($channel);

        return $cf;
    }

    public function createChannelFacebookFundingSource(Channel $channel)
    {
        // if (!isset($channel->channelFacebook)) {
        //     return ResponseService::serverError('Channel is not a Facebook Platform');
        // }

        // $cf = $channel->channelFacebook->service()->getFundingSource();
    }

    public function getCollection(Request $request)
    {
        return ResponseService::serviceUnavailable();

        $search = $request->input('search', null);
        $platform = ChannelPlatformEnum::memberByValue($request->input('platform', null));
        $status = ChannelStatusEnum::memberByValue($request->input('status', null));
        $owner = $request->input('owner', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $channels = Channel::search($search, $platform, $status, $owner, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return ChannelResource::collection($channels);
    }

    public function get(Channel $channel)
    {
        return ResponseService::success('Success', new ChannelResource($channel));
    }

    public function create(CreateChannelRequest $request)
    {
        return ResponseService::serviceUnavailable();

        $channel = ChannelService::create(
            $request->validated()['title'],
            $request->validated()['fb_page_id'] ?? null,
            isset($request->validated()['fb_vertical']) ? FacebookVerticalEnum::memberByValue($request->validated()['fb_vertical']) : null,
            isset($request->validated()['fb_timezone']) ? FacebookTimezoneEnum::memberByValue($request->validated()['fb_timezone']) : null,
            $request->validated()['fb_ad_account'] ?? null,
            $request->validated()['content'],
            $request->validated()['user_access_token'] ?? null,
            $request->validated()['fb_page_id'],
            $request->validated()['facebook_access_token'] ?? null,
            ChannelPlatformEnum::memberByValue($request->validated()['platform']),
            ChannelStatusEnum::memberByValue($request->validated()['status']),
            ChannelFacebookTypeEnum::memberByValue($request->input('type', 1))
        );
        if (isset($channel['error'])) {
            return ResponseService::serverError($channel['message']);
        }
        return ResponseService::successCreate('Channel was created.', new ChannelResource($channel));
    }

    public function update(UpdateChannelRequest $request, Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        $channel = $channel->Service()->update(
            $request->validated()['title'],
            $request->validated()['fb_page_id'] ?? null,
            $request->validated()['fb_ad_account'] ?? null,
            $request->validated()['facebook_access_token'] ?? null,
            $request->validated()['content'],
            ChannelStatusEnum::memberByValue($request->validated()['status'])
        );

        if (isset($channel['error'])) {
            return ResponseService::serverError($channel['message']);
        }

        return ResponseService::successCreate('Channel was updated.', new ChannelResource($channel));
    }

    public function uploadImage(UploadChannelImage $request, Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        $images = $channel->Service()->uploadImages($request->validated()['images']);

        return ResponseService::success('Channel images was uploaded.', ImageResource::collection($images));
    }

    public function setFeaturedImage(Channel $channel, int $image_id)
    {
        return ResponseService::serviceUnavailable();
        $channel->Service()->markAsFeatured($image_id);

        return ResponseService::success('Featured image was set.');
    }

    public function delete(Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->Service()->deleteChannel();

        if (isset($ch['error'])) {
            return ResponseService::serverError($ch['message']);
        }

        return ResponseService::success('Channel was archived.');
    }

    public function deleteMultiple(DeleteMultipleChannelRequest $request)
    {
        return ResponseService::serviceUnavailable();
        return ResponseService::BulkDelete($request->validated()['ids']);
    }

    // public function deleteChildId($child_id)
    // {
    //     $fbch = ChannelFacebookService::deleteChildId($child_id);

    //     return $fbch;
    // }

    public function deleteImage(Channel $channel, int $id)
    {
        return ResponseService::serviceUnavailable();
        $channel->Service()->detachImage($id);

        return ResponseService::success('Channel image was deleted.');
    }

    public function getPermission(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->getPermission($request->code ?: null);
        return $ch;
    }

    public function acceptCode(Request $request)
    {
        return ResponseService::serviceUnavailable();
        if (isset($request->code)) {
            $state = explode('_', $request->state);
            return redirect()->route('get-permission', ['channel' => $state[1], 'code' => $request->code]);
        } else {
            abort(404);
        }
    }

    public function getCampaigns(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->getFacebookCampaigns($request->status ?? '');
        return $ch;
    }

    public function getSingleCampaign(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->getSingleFacebookCampaign($request->campaign_id);
        return $ch;
    }

    public function deleteCampaign(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->deleteFacebookCampaign($request->campaign_id);
        return $ch;
    }

    public function createCampaign(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->createFacebookCampaign(
            $request->name,
            FacebookCampaignObjectiveEnum::memberByValue($request->objective),
            FacebookCampaignStatusEnum::memberByValue($request->status) ?? FacebookCampaignStatusEnum::PAUSED,
            $request->special_ad_category ?? []
        );
        return $ch;
    }

    public function updateCampaign(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->updateFacebookCampaign(
            $request->campaign_id,
            $request->name,
            FacebookCampaignObjectiveEnum::memberByValue($request->objective),
            FacebookCampaignStatusEnum::memberByValue($request->status),
            $request->special_ad_category ?? []
        );
        return $ch;
    }

    public function getAdSets(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->getFacebookAdSets($request->status ?? '');
        return $ch;
    }

    public function getSingleAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->getSingleFacebookAdSet(
            $request->adset_id
        );
        return $ch;
    }

    public function createAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->createFacebookAdSet(
            $request->campaign_id,
            $request->name,
            FacebookAdSetBillingEventEnum::memberByValue($request->billing_event),
            $request->bid_amount,
            FacebookAdSetBidStrategyEnum::memberByValue($request->bid_strategy),
            FacebookBudgetTypeEnum::memberByValue($request->budget_type),
            $request->budget_amount,
            $request->start_time,
            $request->end_time,
            $request->targeting,
            FacebookCampaignStatusEnum::memberByValue($request->status) ?? FacebookCampaignStatusEnum::PAUSED
        );
        return $ch;
    }

    public function updateAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->updateFacebookAdSet(
            $request->adset_id,
            $request->name,
            FacebookAdSetBillingEventEnum::memberByValue($request->billing_event),
            FacebookAdSetBidStrategyEnum::memberByValue($request->bid_strategy),
            $request->bid_amount,
            FacebookBudgetTypeEnum::memberByValue($request->budget_type),
            $request->budget_amount,
            $request->start_time,
            $request->end_time,
            $request->targeting,
            FacebookCampaignStatusEnum::memberByValue($request->status)
        );
        return $ch;
    }

    public function deleteAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->deleteFacebookAdSet($request->adset_id);
        return $ch;
    }

    public function getAds(channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        return $channel->service()->getFacebookAds(
            $request->status ?? '',
            $request->campaign_id,
            $request->adset_id,
        );
    }

    public function createAd(channel $channel, CreateFacebookAdRequest $request)
    {   
        return ResponseService::serviceUnavailable();
        return $channel->service()->createFacebookAd(
            $request->validated()['adset_id'],
            $request->validated()['article_id'],
            $request->validated()['status'] ?? 'PAUSED'
        );
    }

    public function deleteAd(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        return $channel->service()->deleteFacebookAd($request->ad_id);
    }

    public function getSingleAd(channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        return $channel->service()->getSingleFacebookAd(
            $request->ad_id
        );
    }

    public function getFacebookInsight(Request $request, Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->Service()->getFacebookInsight();
        return $ch;
    }

    public function getFacebookDetailedInsight(Request $request, Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->Service()->getFacebookDetailedInsight();
        return $ch;
    }

    public function targetSearchLocationAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchLocationFacebookAdSet($request->q ?? '');
        return $ch;
    }

    public function targetSearchLocaleAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchLocaleFacebookAdSet($request->q);
        return $ch;
    }

    public function targetSearchInterestAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchInterestFacebookAdSet($request->q);
        return $ch;
    }

    public function targetSearchBehaviorAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchBehaviorFacebookAdSet($request->q ?? '');
        return $ch;
    }

    public function targetSearchEducationSchoolAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchEducationSchoolFacebookAdSet($request->q);
        return $ch;
    }

    public function targetSearchEducationMajorAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchEducationMajorFacebookAdSet($request->q);
        return $ch;
    }

    public function targetSearchWorkEmployerAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchWorkEmployerFacebookAdSet($request->q);
        return $ch;
    }

    public function targetSearchJobTitleAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchJobTitleFacebookAdSet($request->q);
        return $ch;
    }

    public function targetSearchCategoryAdSet(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        $ch = $channel->service()->targetingSearchCategoryFacebookAdSet(
            $request->class,
            $request->q ?? ''
        );
        return $ch;
    }

    public function getBusinessManagers()
    {
        return ResponseService::serviceUnavailable();
        $act = ChannelFacebookService::getBusinessManagers();

        return $act;
    }


    public function getParentBMAdAccounts(Request $request)
    {
        return ResponseService::serviceUnavailable();
        $act = ChannelFacebookService::getParentBMAdAccounts(
            $request->input('search', null),
            $request->input('status', 'owned')
        );

        return $act;
    }

    public function getFacebookAdAccounts(Request $request)
    {
        return ResponseService::serviceUnavailable();
        return FacebookAdAccount::search(
            $request->input('search', null),
            $request->input('business_manager_id', null)
        )->get();
    }

    public function updateFacebookAdAccounts(Request $request)
    {
        return ResponseService::serviceUnavailable();
        $result = FacebookService::updateAdAccounts(
            $request->input('business_manager_id', null)
        );

        return $result;
    }

    public function getPages(Request $request)
    {
        return ResponseService::serviceUnavailable();
        $pages = ChannelFacebookService::getFacebookPages(
            $request->channel ?? null,
            $request->status ?? 'owned'
        );
        return $pages;
    }

    public function updateParentBM()
    {
        return ResponseService::serviceUnavailable();
        return ChannelFacebookService::updateParentBM();
    }

    public function getParentBusinessUsers(Request $request)
    {
        return ResponseService::serviceUnavailable();
        return ChannelFacebookService::getParentBusinessUsers($request->type ?? 'users');
    }

    public function getUsersFromChild(Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->getUsersFromChild($channel);
    }

    public function inviteUsersToChild(Channel $channel, AddUserToChannelRequest $request)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->inviteUsersToChild(
            $channel,
            $request->validated()['email']
        );
    }

    public function deleteUserFromChild(Channel $channel, string $user_id)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->deleteUserFromChild(
            $channel,
            $user_id
        );
    }

    public function getPagesFromChild(Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->getPagesFromChild(
            $channel
        );
    }

    public function addPageToChild(Channel $channel, Request $request)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->addPageToChild(
            $channel,
            $request->page_id
        );
    }

    public function deletePageFromChild(Channel $channel, $page)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->deletePageFromChild(
            $channel,
            $page
        );
    }

    public function assignUsersToPage(Channel $channel, $user_id)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->assignUsersToPage(
            $channel,
            $user_id
        );
    }

    public function generateLocForChild(Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->generateLocForChild(
            $channel
        );
    }

    public function getLocForChild(Channel $channel)
    {
        return ResponseService::serviceUnavailable();
        return $channel->channelFacebook->Service()->getLoc($channel);
    }
}

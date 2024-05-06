<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFacebookAdModelRequest;
use App\Http\Requests\UpdateFacebookAdModelRequest;
use App\Http\Resources\FacebookAdResource;
use App\Models\Article;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\FacebookAd;
use App\Models\FacebookAdset;
use App\Models\Services\FacebookAdModelService;
use App\Models\Services\FacebookAdsetModelService;
use App\Models\Site;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FacebookAdController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('facebook-ads', [FacebookAdController::class, 'create']);
        Route::post('facebook-ads/single', [FacebookAdController::class, 'createSingle']);
        Route::post('facebook-ads/multiple-create/test', [FacebookAdController::class, 'multipleCreateTest']);
        Route::put('facebook-ads/{facebookAd}/duplicate', [FacebookAdController::class, 'duplicate']);
        Route::get('facebook-ads/{facebookAd}/toggle-status', [FacebookAdController::class, 'toggleStatus']);
        Route::post('facebook-ads/{facebookAd}', [FacebookAdController::class, 'update']);
        Route::delete('facebook-ads/{facebookAd}', [FacebookAdController::class, 'delete']);
        Route::get('facebook-ads/{facebookAd}', [FacebookAdController::class, 'get']);
        Route::get('facebook-ads', [FacebookAdController::class, 'collection']);
    }

    public function collection(Request $request)
    {
        if (! $request->has('adset_id')) {
            return ResponseService::clientNotAllowed('Ad Set ID is required');
        }

        $search = $request->input('search', null);
        $status = CampaignInAppStatusEnum::memberByValue($request->input('status', null));

        $ads = FacebookAd::search($search, $status)
            ->whereAdsetId($request->input('adset_id'))
            ->latest('created_at')
            ->paginate($request->input('per_page', 10));

        return FacebookAdResource::collection($ads);
    }

    public function get(FacebookAd $facebookAd)
    {
        return new FacebookAdResource($facebookAd);
    }

    public function create(CreateFacebookAdModelRequest $request)
    {
        $ads = FacebookAdModelService::multipleCreate($request);

        $return = [];
        foreach ($ads as $ad) {
            if (isset($ad['error'])) {
                $return[] = ResponseService::clientError('Failed to create Facebook Ad', $ads);
            } else {
                $return[] = ResponseService::successCreate(
                    'Facebook Ad was created',
                    new FacebookAdResource($ad)
                );

            }
        }
        return $return;

    }

    public function createSingle(Request $request)
    {
        $ads = FacebookAdModelService::create(
            FacebookAdset::find($request->adset_id),
            null,
            CampaignInAppStatusEnum::memberByValue($request->status),
            null,
            $request->title,
            $request->primary_text,
            $request->headline,
            $request->description,
            $request->display_link,
            FacebookCallToActionEnum::memberByValue($request->call_to_action),
            $request->image
        );

        return $ads;
    }

    public function duplicate(FacebookAd $facebookAd)
    {
        $ad = $facebookAd->Service()->duplicate();

        if (isset($ad['error'])) {
            return ResponseService::serverError($ad['message']);
        }

        return ResponseService::successCreate(
            'Facebook Ad duplicated successfully',
            new FacebookAdResource($ad)
        );
    }

    public function toggleStatus(FacebookAd $facebookAd)
    {
        $ad = $facebookAd->Service()->toggleStatus();

        if (isset($ad['error'])) {
            return ResponseService::serverError($ad['message']);
        }

        return ResponseService::successCreate(
            "Facebook Ad is now {$ad->fb_status->getLabel()}",
            new FacebookAdResource($ad)
        );
    }

    public function update(UpdateFacebookAdModelRequest $request, FacebookAd $facebookAd)
    {
        if (isset($request->validated()['article_id'])) {
            $article = Article::findOrFail($request->validated()['article_id']);
        }

        if (isset($request->site_id) && $request->site_id) {
            $site = Site::findOrFail($request->site_id);
        }

        if (isset($request->validated()['call_to_action'])) {
            $callToAction = FacebookCallToActionEnum::memberByValue($request->validated()['call_to_action']);
        }

        $ad = $facebookAd->Service()->update(
            $site ?? null,
            CampaignInAppStatusEnum::memberByValue($request->validated()['status']),
            $article ?? null,
            $request->validated()['title'] ?? null,
            $request->validated()['primary_text'] ?? null,
            $request->validated()['headline'] ?? null,
            $request->validated()['description'] ?? null,
            $request->validated()['display_link'] ?? null,
            $callToAction ?? null,
            $request->validated()['image'] ?? null,
        );

        if (isset($ad['error'])) {
            return ResponseService::serverError($ad['message']);
        }

        return ResponseService::success(
            'Facebook Ad was updated',
            new FacebookAdResource($ad)
        );
    }

    public function delete(FacebookAd $facebookAd)
    {
        $facebookAd->Service()->delete();

        return ResponseService::success('Facebook Ad was archived');
    }

    public function multipleCreateTest(Request $request)
    {
        return FacebookAdModelService::createMultipleTest(
            FacebookAdset::findOrFail($request->adset_id),
            $request->input('copies', 0)
        );
    }
}

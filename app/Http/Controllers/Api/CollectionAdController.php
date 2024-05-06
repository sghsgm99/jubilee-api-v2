<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCollectionAdRequest;
use App\Http\Requests\PreviewCollectionAdRequest;
use App\Http\Requests\UpdateCollectionAdRequest;
use App\Services\ResponseService;
use App\Models\User;
use App\Models\Channel;
use App\Models\CollectionAd;
use App\Models\Services\CollectionAdService;
use App\Models\Enums\CollectionAdStatusEnum;
use App\Http\Resources\CollectionAdResource;
use App\Http\Resources\ChannelResource;
use App\Models\CCollection;
use App\Models\CollectionGroup;
use App\Models\FacebookAdset;
use App\Models\FacebookCampaign;

class CollectionAdController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('collection-ads', [CollectionAdController::class, 'create']);
        Route::put('collection-ads/{collectionAd}', [CollectionAdController::class, 'update']);
        Route::get('collection-ads/{collectionAd}', [CollectionAdController::class, 'get']);
        Route::delete('collection-ads/{collectionAd}', [CollectionAdController::class, 'delete']);
        Route::get('collections/{collection}/ads', [CollectionAdController::class, 'getCollection']);
        Route::get('collection-ads/{collectionAd}/duplicate', [CollectionAdController::class, 'duplicate']);
        Route::post('collection-ads/preview', [CollectionAdController::class, 'preview']);
    }

    public function getCollection(CCollection $collection, Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $collectionAds = CollectionAd::search(
            $collection->id, 
            $search,
            $sort,
            $sort_type
        )->paginate($request->input('per_page', 10));

        return CollectionAdResource::collection($collectionAds);
    }

    public function create(CreateCollectionAdRequest $request)
    {
        $collectionAd = CollectionAdService::create(
            CCollection::findOrFail($request->validated()['collection_id']),
            Channel::findOrFail($request->validated()['channel_id']),
            $request->validated()['ad_account_id'],
            FacebookCampaign::findOrFail($request->validated()['campaign_id']),
            FacebookAdset::findOrFail($request->validated()['adset_id']),
            CollectionGroup::findOrFail($request->validated()['group_id']),
            $request->validated()['ads_number'],
            $request->validated()['add_images'],
            $request->validated()['add_title'],
            $request->validated()['add_headline'],
            $request->validated()['add_text'],
            $request->validated()['add_call_to_action'],
            $request->validated()['add_url'],
            CollectionAdStatusEnum::memberByValue($request->validated()['status']),
        );

        if (isset($collectionAd['error'])) {
            return ResponseService::serverError($collectionAd['message']);
        }

        return ResponseService::successCreate('Collection Ads was created.', new CollectionAdResource($collectionAd));
    }

    public function update(UpdateCollectionAdRequest $request, CollectionAd $collectionAd)
    {
        $collectionAd = $collectionAd->Service()->update(
            $request->validated()['channel_id'],
            $request->validated()['ad_account_id'],
            $request->validated()['campaign_id'],
            $request->validated()['adset_id'],
            $request->validated()['ads_number'],
            $request->validated()['add_images'],
            $request->validated()['add_title'],
            $request->validated()['add_headline'],
            $request->validated()['add_text'],
            $request->validated()['add_call_to_action'],
            $request->validated()['add_url'],
            CollectionAdStatusEnum::memberByValue($request->status)
        );

        if (isset($collectionAd['error'])) {
            return ResponseService::serverError($collectionAd['message']);
        }

        return ResponseService::successCreate('Collection Ads was updated.', new CollectionAdResource($collectionAd));
    }

    public function get(CollectionAd $collectionAd)
    {
        return ResponseService::success('Success', new CollectionAdResource($collectionAd));
    }

    public function preview(PreviewCollectionAdRequest $request)
    {
        return CollectionAdService::preview(
            $request->validated()['ads_number'],
            $request->validated()['add_images'],
            $request->validated()['add_title'],
            $request->validated()['add_headline'],
            $request->validated()['add_text'],
            $request->validated()['add_call_to_action'],
            $request->validated()['add_url'],
        );
    }

    public function duplicate(CollectionAd $collectionAd)
    {
        $collectionAd = CollectionAdService::duplicate(
            $collectionAd->collection_id,
            $collectionAd->channel_id,
            $collectionAd->ad_account_id,
            $collectionAd->campaign_id,
            $collectionAd->adset_id,
            $collectionAd->group_id,
            $collectionAd->ads_number,
            $collectionAd->add_images,
            $collectionAd->add_title,
            $collectionAd->add_headline,
            $collectionAd->add_text,
            $collectionAd->add_call_to_action,
            $collectionAd->add_url
        );

        if (isset($collectionAd['error'])) {
            return ResponseService::serverError($collectionAd['message']);
        }

        return ResponseService::successCreate('Collection Ads was duplicated.', new CollectionAdResource($collectionAd));

    }

    public function delete(CollectionAd $collectionAd)
    {
        $collectionAd->Service()->delete();

        return ResponseService::success('Collection Ads was archived');
    }
}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddCreativeToCollectionGroupRequest;
use App\Http\Requests\CreateCollectionGroupRequest;
use App\Http\Requests\DeleteCollectionCreativeRequest;
use App\Http\Requests\DeleteCreativeFromCollectionGroupRequest;
use App\Http\Requests\UpdateCollectionGroupRequest;
use App\Http\Requests\UploadCollectionCreativeRequest;
use App\Services\ResponseService;
use App\Services\ScrapeService;
use App\Models\CCollection;
use App\Models\CollectionCreative;
use App\Models\CollectionGroup;
use App\Models\Services\CollectionService;
use App\Models\Services\CollectionCreativeService;
use App\Models\Services\CollectionGroupService;
use App\Models\Enums\CollectionStatusEnum;
use App\Models\Enums\CollectionCreativeTypeEnum;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\CollectionCreativeResource;
use App\Http\Resources\CollectionGroupResource;
use App\Http\Resources\CollectionGroupCreativeResource;
use App\Models\CollectionGroupCreative;

class CollectionController extends Controller
{
    public static function apiRoutes()
    {
        // Campaign Collection
        Route::get('collections', [CollectionController::class, 'getCollection']);
        Route::post('collections', [CollectionController::class, 'create']);
        Route::get('collections/{collection}', [CollectionController::class, 'get']);
        Route::delete('collections/{collection}', [CollectionController::class, 'delete']);
        Route::put('collections/{collection}', [CollectionController::class, 'update']); // not sure if being used...
        Route::get('collections/{collection}/duplicate', [CollectionController::class, 'duplicateCollection']);
        
        // collection Creatives
        Route::post('collections/search', [CollectionController::class, 'search']);
        Route::post('collections/creative', [CollectionController::class, 'addCreative']);
        Route::delete('collections/creative/delete', [CollectionController::class, 'deleteCreative']);
        Route::get('collections/creatives/all', [CollectionController::class, 'getAllCreatives']);
        Route::post('collections/creative-upload/all', [CollectionController::class, 'uploadCreative']);

        // collection Groups
        Route::post('collections/group', [CollectionController::class, 'createGroup']);
        Route::put('collections/group/{group}', [CollectionController::class, 'updateGroup']);
        Route::get('collections/group/{collection}', [CollectionController::class, 'getGroups']);
        Route::get('collections/creatives/{group}', [CollectionController::class, 'getGroupCreatives']);
        Route::delete('collections/group/{group}', [CollectionController::class, 'detachGroup']);

        // collection Group Creatives
        Route::post('collections/group/{group}/creative', [CollectionController::class, 'addGroupCreative']);
        Route::delete('collections/group/{group}/delete-creatives', [CollectionController::class, 'detachGroupCreatives']);
        Route::post('collections/creative-upload/{group}', [CollectionController::class, 'uploadCreativeToGroup']);
        Route::get('collections-group/{group}/duplicate', [CollectionController::class, 'duplicateGroups']);

        
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = CollectionStatusEnum::memberByValue($request->input('status', null));
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $collections = CCollection::search($search, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return CollectionResource::collection($collections);
    }

    public function create(Request $request)
    {
        $collection = CollectionService::create(
            auth()->user(),
            CollectionStatusEnum::DRAFT(),
            $request->input('name')
        );

        return ResponseService::successCreate('Campaign Collection was created.', new CollectionResource($collection));
    }

    public function get(CCollection $collection)
    {
        return ResponseService::success('Success', new CollectionResource($collection));
    }

    public function duplicateCollection(CCollection $collection, Request $request)
    {
        $collection = $collection->Service()->duplicate($request->input('deep', false));

        if (isset($collection['error'])) {
            return ResponseService::serverError($collection['message']);
        }

        return ResponseService::successCreate('Collection was duplicated.', new CollectionResource($collection));
    }

    public function delete(CCollection $collection)
    {
        if (!$collection->Service()->delete()) {
            return ResponseService::serverError('Campaign Collection cannot be deleted');
        }

        return ResponseService::success('Campaign Collection was archived.');
    }

    public function search(Request $request)
    {
        $currentPage = $request->input('page', 1);
        $perPage = $request->input('per_page', null);
        $through = $request->input('through_txt', null);
        $sel_through = $request->input('sel_through', null);
        $in_link = $request->input('in_link', null);
        $sel_geo = $request->input('sel_geo', null);
        $geo_to = $request->input('geo_to', null);
        $ip = $request->input('on_ip', null);
        $pid = $request->input('page_id', null);

        return ScrapeService::resolve()
            ->getCampaignCollections(
                $currentPage, $through, $in_link, 
                $sel_through, $sel_geo, $geo_to, 
                $ip, $pid, $perPage
            );
    }

    public function createGroup(CreateCollectionGroupRequest $request)
    {
        $group = CollectionGroupService::create(
            $request->validated()['name'],
            $request->validated()['collection_id'],
            $request->validated()['creative_ids']
        );
        
        return ResponseService::successCreate('Campaign Collection Group was created.', new CollectionGroupResource($group));
    }

    public function updateGroup(CollectionGroup $group, UpdateCollectionGroupRequest $request)
    {
        $group = $group->Service()->update($request->validated()['name']);

        return ResponseService::successCreate('Campaign Collection Group was updated.', new CollectionGroupResource($group));
    }

    public function getGroups(CCollection $collection, Request $request)
    {
        return CollectionGroupResource::collection($collection->groups);
    }

    public function duplicateGroups(CollectionGroup $group)
    {
        $group = CollectionGroupService::duplicate(
            "{$group->name} - copy",
            $group->collection_id,
            $group->groupCreatives->pluck('creative_id')->toArray()
        );

        if (isset($group['error'])) {
            return ResponseService::serverError($group['message']);
        }

        return ResponseService::successCreate('Collection Group was duplicated.', new CollectionGroupResource($group));
    }

    public function addCreative(Request $request)
    {
        $data = [
            'page_url' => $request->input('url', null),
            'image' => $request->input('images', null),
            'headline' => $request->input('headline', null),
            'body_text' => $request->input('title', null),
            'description' => $request->input('description', null),
            'geo' => $request->input('geo', null)
        ];

        $creative = CollectionCreativeService::create(
            $data,
            CollectionCreativeTypeEnum::SEARCH()
        );

        return ResponseService::successCreate('Campaign Collection Creative was created.', new CollectionCreativeResource($creative));
    }

    public function deleteCreative(DeleteCollectionCreativeRequest $request)
    {
        CollectionCreativeService::deleteCreatives($request->validated()['creative_ids']);

        return ResponseService::success('Collection Creative was archived.');
    }

    public function getAllCreatives(Request $request)
    {
        $creatives = CollectionCreative::search(
            $request->input('sort', null),
            $request->input('sort_type', null),
            $request->input('type', null),
            $request->input('search', null),
        )->paginate($request->input('per_page', 20));

        return CollectionCreativeResource::collection($creatives);
    }

    public function getGroupCreatives(CollectionGroup $group, Request $request)
    {
        $creatives = CollectionGroupCreative::search(
            $group->id,
            $request->input('sort', null),
            $request->input('sort_type', null),
            $request->input('type', null),
            $request->input('search', null)
        )->paginate($request->input('per_page', 10));

        return CollectionGroupCreativeResource::collection($creatives);
    }

    public function detachGroup(CollectionGroup $group)
    {
        if (!$group->Service()->delete()) {
            return ResponseService::serverError('Campaign Collection Group cannot be deleted');
        }

        return ResponseService::success('Campaign Collection Group was archived.');
    }

    public function detachGroupCreatives(CollectionGroup $group, DeleteCreativeFromCollectionGroupRequest $request)
    {
        $res = $group->Service()->deleteGroupCreatives($request->validated()['group_creative_ids']);

        if(isset($res['error'])) {
            return ResponseService::serverError($res['message']);
        }
        
        return ResponseService::success('Search Creatives was archived.');
    }

    public function addGroupCreative(CollectionGroup $group, AddCreativeToCollectionGroupRequest $request)
    {
        $group->Service()->attachCreatives($request->validated()['creative_ids']);
        
        return ResponseService::successCreate('Campaign Collection Group was added.', new CollectionGroupResource($group));
    }

    public function uploadCreative(UploadCollectionCreativeRequest $request)
    {
        $creative = CollectionCreativeService::createUpload(
            $request->validated()['image'] ?? null,
            CollectionCreativeTypeEnum::UPLOAD()
        );

        return ResponseService::successCreate('Campaign Collection Creative was created.', new CollectionCreativeResource($creative));
    }

    public function uploadCreativeToGroup(CollectionGroup $group, UploadCollectionCreativeRequest $request)
    {
        $creative = CollectionCreativeService::createUpload(
            $request->validated()['image'] ?? null,
            CollectionCreativeTypeEnum::UPLOAD()
        );

        if (isset($creative['error'])) {
            return ResponseService::successCreate($creative['message']);
        }

        $group->Service()->attachCreatives([$creative->id]);

        return ResponseService::successCreate('Campaign Collection Creative was created.', new CollectionCreativeResource($creative));
    }

    public function update(CCollection $collection, Request $request)
    {
        $collection = $collection->Service()->update(
            $request->input('group_id'),
            $request->input('headline1', null),
            $request->input('headline2', null),
            $request->input('text1', null),
            $request->input('text2', null),
            $request->input('action1', null),
            $request->input('action2', null),
            $request->input('urls', null)
        );

        if (isset($collection['error'])) {
            return ResponseService::serverError($collection['message']);
        }

        $collection->Service()->syncFacebookAdAccount($request->input('facebook_ad_account_ids'));

        return ResponseService::successCreate('Campaign Collection was published.', new CollectionResource($collection));
    }
}

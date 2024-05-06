<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachTagsToCampaignRequest;
use App\Http\Requests\CreateUpdateCampaignTagRequest;
use App\Http\Resources\CampaignTagResource;
use App\Models\Campaign;
use App\Models\CampaignTag;
use App\Models\FacebookCampaign;
use App\Models\Services\CampaignTagService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CampaignTagController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('campaign-tags', [CampaignTagController::class, 'create']);
        Route::post('campaign-tags/{campaign}/attach', [CampaignTagController::class, 'attachToCampaign']);
        Route::post('campaign-tags/{facebookCampaign}/attach-fb-campaign', [CampaignTagController::class, 'attachToFacebookCampaign']);
        Route::put('campaign-tags/{tag}', [CampaignTagController::class, 'update']);
        Route::delete('campaign-tags/{tag}', [CampaignTagController::class, 'delete']);
        Route::get('campaign-tags/list', [CampaignTagController::class, 'getList']);
        Route::get('campaign-tags/{tag}', [CampaignTagController::class, 'get']);
        Route::get('campaign-tags', [CampaignTagController::class, 'getCollection']);
    }

    public function getCollection()
    {
        return CampaignTagResource::collection(CampaignTag::all());
    }

    public function getList(Request $request)
    {
        $keyword = $request->get('keyword', null);

        return CampaignTagService::getListOption($keyword);
    }

    public function get(CampaignTag $tag)
    {
        return ResponseService::success(
            'Success',
            new CampaignTagResource($tag)
        );
    }

    public function create(CreateUpdateCampaignTagRequest $request)
    {
        $tag = CampaignTagService::create(
            $request->validated()['label'],
            $request->validated()['color']
        );

        return ResponseService::success(
            'Campaign tag was created',
            new CampaignTagResource($tag)
        );
    }

    public function attachToCampaign(AttachTagsToCampaignRequest $request, Campaign $campaign)
    {
        $campaign->tags()->sync($request->validated()['tag_ids']);

        return ResponseService::success('Tags was attached to campaign.');
    }

    public function attachToFacebookCampaign(AttachTagsToCampaignRequest $request, FacebookCampaign $facebookCampaign)
    {
        $facebookCampaign->tags()->sync($request->validated()['tag_ids']);

        return ResponseService::success('Tags was attached to campaign.');
    }

    public function update(CreateUpdateCampaignTagRequest $request, CampaignTag $tag)
    {
        $tag = $tag->Service()->update(
            $request->validated()['label'],
            $request->validated()['color']
        );

        return ResponseService::success(
            'Campaign tag was updated',
            new CampaignTagResource($tag)
        );
    }

    public function delete(CampaignTag $tag)
    {
        $tag->Service()->delete();

        return ResponseService::success('Campaign tag was archived.');
    }
}

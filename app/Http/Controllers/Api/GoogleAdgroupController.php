<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\GoogleAdgroup;
use App\Models\GoogleCampaign;
use App\Models\Services\GoogleAdgroupModelService;
use App\Http\Resources\GoogleAdgroupResource;
use App\Services\ResponseService;

class GoogleAdgroupController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-adgroups', [GoogleAdgroupController::class, 'getCollection']);
        Route::post('google-adgroups', [GoogleAdgroupController::class, 'create']);
        Route::put('google-adgroups/{googleAdgroup}', [GoogleAdgroupController::class, 'update']);
        Route::get('google-adgroups/{googleAdgroup}', [GoogleAdgroupController::class, 'getSingle']);
        Route::get('google-adgroups/updateStatus/{googleAdgroup}', [GoogleAdgroupController::class, 'updateStatus']);
        Route::delete('google-adgroups/{googleAdgroup}', [GoogleAdgroupController::class, 'delete']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $campaign_id = $request->input('campaign', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $adgroups = GoogleAdgroup::search(
            $search,
            $campaign_id,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleAdgroupResource::collection($adgroups);
    }

    public function create(Request $request)
    {
        $adgroup = GoogleAdgroupModelService::create(
            GoogleCampaign::findOrFail($request['campaign_id']),
            $request->input('title'),
            $request->input('bid'),
            $request->input('type'),
            $request->input('status'),
            $request->input('data', null)
        );
        
        if (isset($adgroup['error'])) {
            return ResponseService::serverError('Ad group was not created.');
        }

        return ResponseService::successCreate('Ad group was created.', new GoogleAdgroupResource($adgroup));
    }

    public function update(GoogleAdgroup $googleAdgroup, Request $request)
    {
        $adgroup = $googleAdgroup->Service()->update(
            $request->input('title'),
            $request->input('bid'),
            $request->input('status'),
            $request->input('data', null)
        );

        if (isset($adgroup['error'])) {
            return ResponseService::serverError('Ad group was not updated.');
        }

        return ResponseService::successCreate('Ad group was updated.', new GoogleAdgroupResource($adgroup));
    }

    public function getSingle(GoogleAdgroup $googleAdgroup)
    {
        return new GoogleAdgroupResource($googleAdgroup);
    }

    public function updateStatus(GoogleAdgroup $googleAdgroup, Request $request)
    {
        $adgroup = $googleAdgroup->Service()->updateStatus(
            $request->input('status')
        );

        if (isset($adgroup['error'])) {
            return ResponseService::serverError('Ad group was not updated.');
        }

        return ResponseService::successCreate('Ad group was updated.', new GoogleAdgroupResource($adgroup));
    }

    public function delete(GoogleAdgroup $googleAdgroup)
    {
        $googleAdgroup->Service()->delete();

        return ResponseService::successCreate('Ad group was deleted successfully.');
    }
}

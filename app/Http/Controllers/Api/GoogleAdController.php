<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\GoogleAd;
use App\Models\GoogleAdgroup;
use App\Models\Services\GoogleAdModelService;
use App\Http\Resources\GoogleAdResource;
use App\Services\ResponseService;
use App\Services\OpenAIService;

class GoogleAdController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-ads', [GoogleAdController::class, 'getCollection']);
        Route::post('google-ads', [GoogleAdController::class, 'create']);
        Route::put('google-ads/{googleAd}', [GoogleAdController::class, 'update']);
        Route::get('google-ads/{googleAd}', [GoogleAdController::class, 'getSingle']);
        Route::get('google-ads/updateStatus/{googleAd}', [GoogleAdController::class, 'updateStatus']);
        Route::delete('google-ads/{googleAd}', [GoogleAdController::class, 'delete']);
        Route::post('google-ads/generateText', [GoogleAdController::class, 'generateText']);
        Route::post('google-ads/generateImage', [GoogleAdController::class, 'generateImage']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $adgroup_id = $request->input('adgroup', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $adgroups = GoogleAd::search(
            $search,
            $adgroup_id,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleAdResource::collection($adgroups);
    }

    public function create(Request $request)
    {
        $ad = GoogleAdModelService::create(
            GoogleAdgroup::findOrFail($request['adgroup_id']),
            $request->input('type'),
            $request->input('status'),
            $request->input('data')
        );
        
        if (isset($ad['error'])) {
            return ResponseService::serverError('Ad was not created.');
        }

        return ResponseService::successCreate('Ad was created.', new GoogleAdResource($ad));
    }

    public function update(GoogleAd $googleAd, Request $request)
    {
        $ad = $googleAd->Service()->update(
            $request->input('status'),
            $request->input('data')
        );

        if (isset($ad['error'])) {
            return ResponseService::serverError('Ad was not updated.');
        }

        return ResponseService::successCreate('Ad was updated.', new GoogleAdResource($ad));
    }

    public function getSingle(GoogleAd $googleAd)
    {
        return new GoogleAdResource($googleAd);
    }

    public function updateStatus(GoogleAd $googleAd, Request $request)
    {
        $ad = $googleAd->Service()->updateStatus(
            $request->input('status')
        );

        if (isset($ad['error'])) {
            return ResponseService::serverError('Ad was not updated.');
        }

        return ResponseService::successCreate('Ad was updated.', new GoogleAdResource($ad));
    }

    public function delete(GoogleAd $googleAd)
    {
        $googleAd->Service()->delete();

        return ResponseService::successCreate('Ad was deleted successfully.');
    }

    public function generateText(Request $request)
    {
        $keyword = $request->input('keyword');
        $describe = $request->input('describe');
        $writing = $request->input('writing');
        $count = $request->input('count');
        $limit = $request->input('limit');
        $category = $request->input('category');
        
        $text = "create a google display ads $category for $keyword. $count $category. write in $writing tone. $describe. limit $limit characters.";
        
        return OpenAIService::resolve()->generateAIText($text);
    }

    public function generateImage(Request $request)
    {
        $count = $request->input('count');
        $size = $request->input('size');
        $description = $request->input('description');

        return OpenAIService::resolve()->generateAIImage($description, $count, $size);
    }
}

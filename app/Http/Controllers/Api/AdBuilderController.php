<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Controller;
use App\Models\AdBuilder;
use App\Models\User;
use App\Services\ResponseService;
use App\Http\Resources\AdBuilderResource;
use App\Http\Requests\CreateAdBuilderRequest;
use App\Http\Requests\UpdateAdBuilderRequest;
use App\Models\Services\AdBuilderService;

class AdBuilderController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('adbuilders', [AdBuilderController::class, 'getCollection']);
        Route::post('adbuilders', [AdBuilderController::class, 'create']);
        Route::get('adbuilders/{adbuilder}', [AdBuilderController::class, 'get']);
        Route::put('adbuilders/{adbuilder}', [AdBuilderController::class, 'update']);
        Route::delete('adbuilders/{adbuilder}', [AdBuilderController::class, 'delete']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $adbuilder = AdBuilder::search($search, $sort, $sort_type)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return AdBuilderResource::collection($adbuilder);
    }

    public function create(CreateAdBuilderRequest $request)
    {
        $adbuilder = AdBuilderService::create(
            $request->validated()['name'],
            $request->validated()['url'],
            $request->validated()['gjs_components'],
            $request->validated()['gjs_style'],
            $request->validated()['gjs_html'],
            $request->validated()['gjs_css']
        );
        
        return ResponseService::successCreate('Ad Builder was created.', new AdBuilderResource($adbuilder));
    }

    public function get(AdBuilder $adbuilder)
    {
        $v = new AdBuilderResource($adbuilder);

        return response()->json([
            'gjs-components' => json_decode($v['gjs_components']),
            'gjs-style' => json_decode($v['gjs_style']),
            'gjs-html' => $v['gjs_html'],
            'gjs-css' => $v['gjs_css']
        ]);
    }

    public function update(UpdateAdBuilderRequest $request, AdBuilder $adbuilder)
    {
        $adbuilder->Service()->update(
            $request->validated()['gjs_components'],
            $request->validated()['gjs_style'],
            $request->validated()['gjs_html'],
            $request->validated()['gjs_css']
        );

        return ResponseService::success('Ad Builder was updated.', new AdBuilderResource($adbuilder));
    }

    public function delete(AdBuilder $adbuilder)
    {
        $adbuilder->Service()->delete();
        
        return ResponseService::success('Ad Builder was deleted.');
    }
}

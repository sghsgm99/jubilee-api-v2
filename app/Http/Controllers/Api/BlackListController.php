<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BlackList;
use App\Services\ResponseService;
use App\Http\Resources\BlackListResource;
use App\Models\Services\BlackListService;
use App\Http\Requests\CreateBlackListRequest;
use App\Http\Requests\UpdateBlackListRequest;
use App\Models\Enums\BlackListStatusEnum;
use App\Models\Enums\BlackListTypeEnum;

class BlackListController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('blacklists', [BlackListController::class, 'create']);
        Route::get('blacklists', [BlackListController::class, 'getCollection']);
        Route::get('blacklists/{blacklist}', [BlackListController::class, 'get']);
        Route::put('blacklists/{blacklist}', [BlackListController::class, 'update']);
        Route::delete('blacklists/{blacklist}', [BlackListController::class, 'delete']);
    }

    public function create(CreateBlackListRequest $request)
    {
        $blacklist = BlackListService::create(
            Auth::user(),
            $request->validated()['name'],
            $request->domain,
            $request->subdomain,
            BlackListTypeEnum::memberByValue($request->validated()['type']),
            BlackListStatusEnum::memberByValue($request->validated()['status'])
        );
        
        return ResponseService::successCreate('BlackList was created.', new BlackListResource($blacklist));
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $blacklists = BlackList::search($search, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return BlackListResource::collection($blacklists);
    }

    public function get(BlackList $blacklist)
    {
        return ResponseService::success('Success', new BlackListResource($blacklist));
    }

    public function update(UpdateBlackListRequest $request, BlackList $blacklist)
    {
        $blacklist = $blacklist->Service()->update(
            $request->validated()['name'],
            $request->domain,
            $request->subdomain,
            BlackListTypeEnum::memberByValue($request->validated()['type']),
            BlackListStatusEnum::memberByValue($request->validated()['status'])
        );

        if (isset($blacklist['error'])) {
            return ResponseService::serverError($blacklist['message']);
        }

        return ResponseService::successCreate('BlackList was updated.', new BlackListResource($blacklist));
    }

    public function delete(BlackList $blacklist)
    {
        if (!$blacklist->Service()->delete()) {
            return ResponseService::serverError('BlackList cannot be deleted');
        }

        return ResponseService::success('BlackList was archived.');
    }
}

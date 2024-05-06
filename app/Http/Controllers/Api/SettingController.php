<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Services\SettingService;
use App\Models\Setting;
use App\Models\User;
use App\Services\ResponseService;

class SettingController extends Controller
{
    public static function apiRoutes()
    {
        // Route::post('settings', [SettingController::class, 'create']);
        Route::put('settings/{user}', [SettingController::class, 'update']);
        // Route::delete('settings/{setting}', [SettingController::class, 'delete']);
        Route::get('settings/{user}', [SettingController::class, 'get']);
        // Route::get('settings', [SettingController::class, 'getCollection']);
    }

    public function getCollection()
    {
        return SettingResource::collection(Setting::all());
    }

    public function get(User $user)
    {
        return ResponseService::success('Success', new SettingResource($user->setting));
    }

    public function update(UpdateSettingRequest $request, User $user)
    {
        $user->setting->Service()->update(
            $request->validated()['user'],
            $request->validated()['account']
        );
        return ResponseService::success('Success', new SettingResource($user->setting));
    }

    public function delete(Setting $setting)
    {
        $setting->Service()->delete();
        return ResponseService::success('Site was archived.');
    }

}

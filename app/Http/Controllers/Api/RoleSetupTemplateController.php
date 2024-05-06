<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoleSetupTemplateRequest;
use App\Http\Resources\RoleSetupTemplateResource;
use App\Models\RoleSetupTemplate;
use App\Models\Services\RoleSetupTemplateService;
use App\Services\ResponseService;
use App\Models\Enums\RoleTypeEnum;

class RoleSetupTemplateController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('userrole', [RoleSetupTemplateController::class, 'create']);

        Route::put('userrole/{userrole}', [RoleSetupTemplateController::class, 'update']);

        Route::delete('userrole/bulkdelete', [RoleSetupTemplateController::class, 'bulkDelete']);
        Route::delete('userrole/{userrole}', [RoleSetupTemplateController::class, 'destroy']);

        Route::get('userrole', [RoleSetupTemplateController::class, 'getCollection']);
        Route::get('userrole/{userrole}', [RoleSetupTemplateController::class, 'get']);
        Route::get('userrole/search/{search}', [RoleSetupTemplateController::class, 'search']);
    }

    public function getCollection()
    {
        return RoleSetupTemplateResource::collection(RoleSetupTemplate::all());
    }

    public function get(RoleSetupTemplate $userrole)
    {
        return new RoleSetupTemplateResource($userrole);
    }

    public function search($search){
        return RoleSetupTemplateResource::collection(RoleSetupTemplateService::globalSearch($search));
    }

    public function create(CreateRoleSetupTemplateRequest $request)
    {
        $userrole = RoleSetupTemplateService::create(
            RoleTypeEnum::memberByValue($request->validated()['role_id']),
            $request->validated()['setup_name'],
            $request->validated()['setup']
        );

        return $userrole;
    }

    public function update(CreateRoleSetupTemplateRequest $request, RoleSetupTemplate $userrole)
    {
        return $userrole->Service()->updateRoleSetupTemplate(
            RoleTypeEnum::memberByValue($request->validated()['role_id']),
            $request->validated()['setup_name'],
            $request->validated()['setup']
        );
    }

    public function destroy(RoleSetupTemplate $userrole)
    {
        return ResponseService::success('Role Setup Template deleted successfully.', $userrole->delete());
    }

    public function bulkDelete(Request $request)
    {
        return RoleSetupTemplateService::bulkDelete($request['ids']);
    }
}

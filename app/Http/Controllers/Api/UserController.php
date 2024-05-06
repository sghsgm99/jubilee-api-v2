<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UserAvatarRequest;
use App\Jobs\ProcessTestJob;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\DeleteMultipleUserRequest;
use App\Http\Requests\ExportUserListRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\RoleSetupTemplateResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserResourceCollection;
use App\Models\Account;
use App\Models\Enums\ExportFileTypeEnum;
use App\Models\User;
use App\Models\RoleSetupTemplate;
use App\Models\Services\UserService;
use App\Models\Enums\RoleTypeEnum;

class UserController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('users/role-templates', [UserController::class, 'getRoleTemplateCollection']);
        Route::post('users', [UserController::class, 'create']);
        Route::post('users/export', [UserController::class, 'exportUsers']);
        Route::post('users/{user}/avatar-upload', [UserController::class, 'uploadAvatar']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/delete', [UserController::class, 'deleteMultiple']);
        Route::delete('users/{user}/avatar-remove', [UserController::class, 'deleteAvatar']);
        Route::delete('users/{user}', [UserController::class, 'delete']);
        Route::get('users/list-option', [UserController::class, 'getUserLists']);
        Route::get('users/{user}', [UserController::class, 'get']);
        Route::get('users', [UserController::class, 'getCollection']);

        Route::get('process-user-job/{user}', [UserController::class, 'processUserJob']);
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = $request->input('status', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $users = User::search($search, $status, null, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return UserResource::collection($users);
    }

    public function getRoleTemplateCollection()
    {
        return ResponseService::success('Success', RoleSetupTemplateResource::collection(RoleSetupTemplate::all()));
    }

    public function getUserLists(Request $request)
    {
        $keyword = $request->get('keyword', null);

        return ResponseService::success('Success', UserService::getListOption($keyword));
    }

    public function get($id)
    {
        return ResponseService::success('Success', new UserResource(User::withTrashed()->where('id', $id)->first()));
    }

    public function create(CreateUserRequest $request)
    {
        $user = UserService::create(
            auth()->user()->account,
            $request->validated()['first_name'],
            $request->validated()['last_name'],
            $request->validated()['email'],
            $request->validated()['password'],
            RoleSetupTemplate::whereRoleId(RoleTypeEnum::memberByValue($request->validated()['role_id']))->first(),
            $request->validated()['is_owner']
        );

        if (isset($user['error'])) {
            return ResponseService::serverError($user['message']);
        } else {
            return ResponseService::successCreate('User was created.', new UserResource($user));
        }
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::withTrashed()->where('id', $id)->first();

        if (!$request->validated()['is_owner']) {
            // check all users in account if has is_owner = true
            $check = User::notUserId()
                ->whereAccountId($user->account_id)
                ->isOwner()
                ->first();

            if (!$check) {
                return ResponseService::clientNotAllowed("No owners will be left for the account : {$user->account->company_name}");
            }
        }

        $user->Service()->update(
            $request->validated()['first_name'],
            $request->validated()['last_name'],
            $request->validated()['email'],
            $request->validated()['password'],
            $request->validated()['is_owner'],
            $request->validated()['is_active'],
            RoleSetupTemplate::whereRoleId(RoleTypeEnum::memberByValue($request->validated()['role_id']))->first(),

        );

        return ResponseService::success('User was updated.', new UserResource($user));
    }

    public function uploadAvatar(UserAvatarRequest $request, User $user)
    {
        $avatar = $user->Service()->updateAvatar($request->file('image'));

        return ResponseService::success('Avatar was uploaded.', $avatar);
    }

    public function deleteAvatar(User $user)
    {
        if ($image = $user->image) {
            $user->Service()->detachImage($image->id);
        }

        return ResponseService::success('User avatar was deleted.');
    }

    public function delete(User $user)
    {
        $user->Service()->delete();

        return ResponseService::success('User was archived.');
    }

    public function deleteMultiple(DeleteMultipleUserRequest $request)
    {
        return UserService::BulkDelete($request->validated()['ids']);
    }

    public function exportUsers(ExportUserListRequest $request)
    {
        return UserService::exportUserList(
            $request->validated()['ids'],
            ExportFileTypeEnum::memberByValue($request->validated()['filetype'])
        );
    }

    public function processUserJob(User $user)
    {
        for ($x = 0; $x <= 10; $x++) {
            ProcessTestJob::dispatch($user)->delay(now()->addSeconds(2));
        }

        return 'done';
    }
}

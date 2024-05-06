<?php

namespace App\Http\Controllers\Api;


use App\Models\User;
use App\Models\Account;
use App\Scopes\AccountScope;
use App\Models\RoleSetupTemplate;
use App\Models\Enums\RoleTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Services\UserService;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\SuperAdminUserResource;
use App\Http\Requests\CreateUserSuperAdminRequest;
use App\Http\Requests\CreateAccountSuperAdminRequest;
use App\Http\Requests\UpdateAccountSuperAdminRequest;
use App\Http\Requests\UpdateUserSuperAdminRequest;
use App\Http\Resources\AccountResource;
use App\Models\Services\AccountService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


class SuperAdminController extends Controller
{
    public static function apiRoutes()
    {
        // CRUD Account
        Route::post('super-admin/account', [SuperAdminController::class, 'createAccount']);
        Route::get('super-admin/account', [SuperAdminController::class, 'getAccountCollection']);
        Route::get('super-admin/account/{account}', [SuperAdminController::class, 'getAccount']);
        Route::post('super-admin/account/{account}', [SuperAdminController::class, 'updateAccount']);
        Route::delete('super-admin/account/{account}', [SuperAdminController::class, 'deleteAccount']);

        // CRUD User
        Route::post('super-admin/user', [SuperAdminController::class, 'createUser']);
        Route::get('super-admin/user', [SuperAdminController::class, 'getUserCollection']);
        Route::get('super-admin/user/{user}', [SuperAdminController::class, 'getUser']);
        Route::put('super-admin/user/{user}', [SuperAdminController::class, 'updateUser']);
        Route::delete('super-admin/user/{user}', [SuperAdminController::class, 'deleteUser']);
    }

    public function createUser(CreateUserSuperAdminRequest $request)
    {
        $user = UserService::create(
            Account::findOrFail($request->validated()['account_id']),
            $request->validated()['first_name'],
            $request->validated()['last_name'],
            $request->validated()['email'],
            $request->validated()['password'],
            RoleSetupTemplate::whereRoleId(RoleTypeEnum::memberByValue($request->validated()['role_id']))->first(),
            $request->validated()['is_owner']
        );

        return ResponseService::successCreate('User was created.', new SuperAdminUserResource($user));
    }

    public function getUserCollection(Request $request)
    {
        $search = $request->input('search', null);
        $status = $request->input('status', null);
        $account_id = $request->input('account_id', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $users = User::withoutGlobalScope(AccountScope::class)
                    ->withTrashed()
                    ->search($search, $status, $account_id, $sort, $sort_type)
                    ->paginate($request->input('per_page', 10));

        return SuperAdminUserResource::collection($users);
    }

    public function getUser($id)
    {
        return ResponseService::success('Success', new SuperAdminUserResource(User::withoutGlobalScope(AccountScope::class)->withTrashed()->where('id', $id)->first()));
    }

    public function updateUser(UpdateUserSuperAdminRequest $request, $id)
    {
        $user = User::withoutGlobalScope(AccountScope::class)->withTrashed()->where('id', $id)->first();

        if (!$request->validated()['is_owner']) {
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

        return ResponseService::success('User was updated.', new SuperAdminUserResource($user));
    }

    public function deleteUser($id)
    {
        $user = User::withoutGlobalScope(AccountScope::class)->withTrashed()->where('id', $id)->first();
        $user->Service()->delete();

        return ResponseService::success('User was archived.');
    }

    public function createAccount(CreateAccountSuperAdminRequest $request)
    {
        $account = AccountService::createSuperAdmin(
            $request->validated()['company_name'],
            $request->validated()['facebook_app_id'] ?? null,
            $request->validated()['facebook_app_secret'] ?? null,
            $request->validated()['facebook_business_manager_id'] ?? null,
            $request->validated()['facebook_access_token'] ?? null,
            $request->validated()['facebook_line_of_credit_id'] ?? null,
            $request->validated()['facebook_primary_page_id'] ?? null,
            $request->validated()['report_token'] ?? null,
            $request->validated()['view_id'] ?? null,
            $request->file('analytic_file') ?? null,
            $request->validated()['analytic_script'] ?? null
        );

        return ResponseService::successCreate('Account was created', new AccountResource($account));
    }

    public function getAccountCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $account = Account::search($search, $sort, $sort_type)
        ->paginate($request->input('per_page', 10));

        return AccountResource::collection($account);
    }

    public function getAccount(Account $account)
    {
        return new AccountResource($account);
    }

    public function updateAccount(UpdateAccountSuperAdminRequest $request, Account $account)
    {
        $account->Service()->updateSuperAdmin(
            $request->validated()['company_name'],
            $request->validated()['facebook_app_id'] ?? null,
            $request->validated()['facebook_app_secret'] ?? null,
            $request->validated()['facebook_business_manager_id'] ?? null,
            $request->validated()['facebook_access_token'] ?? null,
            $request->validated()['facebook_line_of_credit_id'] ?? null,
            $request->validated()['facebook_primary_page_id'] ?? null,
            $request->validated()['report_token'] ?? null,
            $request->validated()['view_id'] ?? null,
            $request->file('analytic_file') ?? null,
            $request->validated()['analytic_script'] ?? null
        );

        return ResponseService::successCreate('Account was updated.', new AccountResource($account));
    }

    public function deleteAccount(Account $account)
    {
        return $account->Service()->delete();
    }
}

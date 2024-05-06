<?php

namespace App\Models\Services;

use App\Exports\UsersExport;
use App\Models\Account;
use App\Models\Enums\ExportFileTypeEnum;
use App\Models\Setting;
use App\Models\User;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var User
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->model = $user;
    }

    public static function create(
        Account $account,
        string $first_name,
        string $last_name,
        string $email,
        string $password,
        object $setup,
        bool $is_owner = false
    ) {
        DB::beginTransaction();

        try {
            $user = new User();
            $user->account_id = $account->id;
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->is_owner = $is_owner;
            $user->role_id = $setup->role_id;
            $user->save();

            $setting = new Setting();
            $setting->user_id = $user->id;
            $setting->account_id = $account->id;
            $setting->save();

            DB::commit();

            return $user;
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['error' => true, 'message' => $th->getMessage()];
        }
    }

    public function update(
        string $first_name,
        string $last_name,
        string $email,
        string $password = null,
        bool $is_owner,
        bool $is_active,
        object $setup
    ) {

        $this->user->first_name = $first_name;
        $this->user->last_name = $last_name;
        $this->user->email = $email;
        $this->user->is_owner = $is_owner;
        $this->user->role_id = $setup->role_id;

        if ($password) {
            $this->user->password = Hash::make($password);
        }

        if (!$is_active) {
            $this->user->delete();
        } elseif ($is_active && $this->user->deleted_at) {
            $this->user->restore();
        }

        $this->user->save();
        return $this->user->fresh();
    }

    public function updateAvatar(UploadedFile $file)
    {
        $avatar = $this->user->FileServiceFactory()->uploadFile($file, 'user_avatar');

        ImageService::updateOrCreate($this->user, $file, $avatar['name']);

        return $avatar;
    }

    public static function getListOption(string $keyword = null)
    {
        $query = User::query();

        if ($keyword) {
            $query->where('first_name', 'LIKE', "%{$keyword}%")
                ->orWhere('last_name', 'LIKE', "%{$keyword}%");
        }

        $users = $query->get(['id', 'first_name', 'last_name']);

        $filtered_users[] = ['id' => null, 'name' => 'No Author'];
        foreach ($users as $user) {
            $filtered_users[] = [
                'id' => $user->id,
                'name' => $user->full_name,
            ];
        }

        return $filtered_users;
    }

    public static function bulkDelete(array $ids)
    {
        $failedDelete = [];
        $successDelete = [];
        foreach ($ids as $id) {
            $user = User::onlyTrashed()->where('id', $id)->first();
            if ($user == null) {
                $failedDelete[] = [
                    "id" => $id,
                    "status" => "Unarchived",
                    "message" => "Failed to delete user. current status is Active."
                ];
            } else {
                $user->Service()->forceDelete();
                $successDelete[] = [
                    "id" => $id,
                    "status" => "Deleted",
                    "message" => "User was delete permanently.",
                    "data" => $user,
                ];
            }
        }
        $response = [
            "archived" => $successDelete,
            "unarchived" => $failedDelete
        ];
        return $response;
    }

    public static function exportUserList(array $ids, ExportFileTypeEnum $file_extension)
    {
        $date = date('Y-m-d');
        return (new UsersExport($ids))->download($date .'_users.' . $file_extension->key);
    }
}

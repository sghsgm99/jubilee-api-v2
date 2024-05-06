<?php

namespace App\Models\Services;

use App\Models\Enums\RoleTypeEnum;
use App\Models\Setting;
use App\Models\User;

class SettingService extends ModelService
{
    /**
     * @var Setting
     */
    private $Setting;

    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
        $this->model = $setting; // required
    }

    public static function create( User $user )
    {
        $setting = new Setting();
        $setting->account_id = $user->account_id;
        $setting->user_id = $user->id;
        $setting->save();
        return $setting;
    }

    public function update(
        array $user,
        array $account
    )
    {
        // update accounts if user is owner
        if($user['is_owner']) {
            $this->setting->account->company_name = $account['company_name'];
            $this->setting->account->save();
        }

        // update users
        $this->setting->user->first_name = $user['first_name'];
        $this->setting->user->last_name = $user['last_name'];
        $this->setting->user->email = $user['email'];
        $this->setting->user->is_owner = $user['is_owner'];
        $this->setting->user->role_id = $user['role_id'];
        $this->setting->user->save();

        return $this->setting->fresh();
    }
}

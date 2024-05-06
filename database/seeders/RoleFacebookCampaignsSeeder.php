<?php

namespace Database\Seeders;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleFacebookCampaignsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $new_role_setup = [
            'page' => PageTypeEnum::FACEBOOK_CAMPAIGNS,
            'page_name' => 'FacebookCampaigns',
            'permission' => [
                PermissionTypeEnum::CREATE,
                PermissionTypeEnum::READ,
                PermissionTypeEnum::UPDATE,
                PermissionTypeEnum::DELETE,
            ],
        ];

        $role_admin = RoleSetupTemplate::whereRoleId(RoleTypeEnum::ADMINISTRATOR())->first();
        $admin_role_setup = $role_admin->setup;
        $admin_role_setup[] = $new_role_setup;
        $role_admin->setup = $admin_role_setup;
        $role_admin->save();

        $new_role_setup['permission'] = [
            PermissionTypeEnum::READ,
            PermissionTypeEnum::UPDATE,
        ];

        $role_editor = RoleSetupTemplate::whereRoleId(RoleTypeEnum::EDITOR())->first();
        $editor_role_setup = $role_editor->setup;
        $editor_role_setup[] = $new_role_setup;
        $role_editor->setup = $editor_role_setup;
        $role_editor->save();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use Illuminate\Database\Seeder;

class RoleAdLibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $new_role_setup = [
            'page' => PageTypeEnum::AD_LIBRARY,
            'page_name' => 'AdLibrary',
            'permission' => [
                PermissionTypeEnum::CREATE,
                PermissionTypeEnum::READ,
                PermissionTypeEnum::UPDATE,
                PermissionTypeEnum::DELETE,
            ]
        ];

        $role = RoleSetupTemplate::whereRoleId(RoleTypeEnum::ADMINISTRATOR())->first();
        $role_setup = $role->setup;
        $role_setup[] = $new_role_setup;
        $role->setup = $role_setup;
        $role->save();
    }
}

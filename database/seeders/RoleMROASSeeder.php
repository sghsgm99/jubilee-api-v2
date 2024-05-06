<?php

namespace Database\Seeders;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleMROASSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $new_role_setup = [
            [
                'page' => PageTypeEnum::MROAS,
                'page_name' => 'MROAS Manager',
                'permission' => [
                    PermissionTypeEnum::CREATE,
                    PermissionTypeEnum::READ,
                    PermissionTypeEnum::UPDATE,
                    PermissionTypeEnum::DELETE,
                ],
            ]
        ];

        $role = RoleSetupTemplate::whereRoleId(RoleTypeEnum::ADMINISTRATOR())->first();
        $role_setup = $role->setup;
        $role_setup[] = $new_role_setup[0];
        $role->setup = $role_setup;
        $role->save();

        $users = User::where('role_id', '=', RoleTypeEnum::ADMINISTRATOR()->value)->get();

        foreach ($users as $user) {
            $user->role_setup = $role_setup;
            $user->save();
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\User;
use App\Models\AdPartner;
use Illuminate\Database\Seeder;

class RoleRootSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $new_role =
        [
            'role' => RoleTypeEnum::ROOT(),
            'setup_name' => 'Root',
            'setup' => [
                [
                    'page' => PageTypeEnum::USERS,
                    'page_name' => 'Users',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::ACCOUNTS,
                    'page_name' => 'Accounts',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ]
            ]
        ];

        $role = RoleSetupTemplate::whereRoleId($new_role['role'])->first();
        $role->setup = $new_role['setup'];
        $role->save();

        $users = User::where('role_id', '=' , $new_role['role']->value)->get();

        foreach ($users as $user) {
            $user->role_id = $new_role['role'];
            $user->save();
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\User;
use App\Models\AdPartner;
use Illuminate\Database\Seeder;

class RoleAdBuilderSeeder extends Seeder
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
            'role' => RoleTypeEnum::ADMINISTRATOR(),
            'setup_name' => 'Administrator',
            'setup' => [
                [
                    'page' => PageTypeEnum::ARTICLES,
                    'page_name' => 'Articles',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::SITES,
                    'page_name' => 'Sites',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::CHANNELS,
                    'page_name' => 'Channels',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::CAMPAIGNS,
                    'page_name' => 'Campaigns',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::ANALYTICS,
                    'page_name' => 'Analytics',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
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
                    'page' => PageTypeEnum::SETTINGS,
                    'page_name' => 'Settings',
                    'permission' => [
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE
                    ],
                ],
                [
                    'page' => PageTypeEnum::ADTEMPLATES,
                    'page_name' => 'AdTemplates',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::REPORTS,
                    'page_name' => 'Reports',
                    'permission' => [
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE
                    ],
                ],
                [
                    'page' => PageTypeEnum::CMANAGERS,
                    'page_name' => 'CManagers',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::ADPARTNERS,
                    'page_name' => 'AdPartners',
                    'permission' => [
                        PermissionTypeEnum::CREATE,
                        PermissionTypeEnum::READ,
                        PermissionTypeEnum::UPDATE,
                        PermissionTypeEnum::DELETE,
                    ],
                ],
                [
                    'page' => PageTypeEnum::ADBUILDERS,
                    'page_name' => 'AdBuilders',
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
            $user->role_setup = $new_role['setup'];
            $user->save();
        }
    }
}

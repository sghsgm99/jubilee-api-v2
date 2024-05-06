<?php

namespace Database\Seeders;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\User;
use App\Models\AdPartner;
use Illuminate\Database\Seeder;

class RoleRuleSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $new_role_setup =
        [
            'page' => PageTypeEnum::RULESETS,
            'page_name' => 'RuleSets',
            'permission' => [
                PermissionTypeEnum::CREATE,
                PermissionTypeEnum::READ,
                PermissionTypeEnum::UPDATE,
                PermissionTypeEnum::DELETE,
            ],
        ];

        $role = RoleSetupTemplate::whereRoleId(RoleTypeEnum::ADMINISTRATOR())->first();
        $role_setup = $role->setup;
        $role_setup[PageTypeEnum::RULESETS - 1] = $new_role_setup;
        $role->setup = $role_setup;
        $role->save();
        
        $users = User::where('role_id', '=', RoleTypeEnum::ADMINISTRATOR()->value)->get();

        foreach ($users as $user) {
            $user->role_setup = $role_setup;
            $user->save();
        }
    }
}

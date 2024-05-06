<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Enums\RoleTypeEnum;
use App\Models\RoleSetupTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BaseSeeder extends Seeder
{
    public function run()
    {
        /**
         * Setup DS account
         */
        $account = new Account();
        $account->company_name = 'Disrupt Social';
        $account->facebook_app_id = '255089593224121';
        $account->facebook_app_secret = 'b5c03dd282dea690d364c0daf57adcd7';
        $account->facebook_business_manager_id = '219899162802586';
        $account->facebook_access_token = 'EAADoAKyXC7kBACB2SukoZB0kkMRQ7mXAw8qFiqtxiZCaCPJyU4ZA5W2eX4jZAtclzi5FRAraQ03sRVunZBiTIh0d7JmDocWUhn9qGPWOZAlIYyUA7MCrBcSmDATfq9ucluXZALW5yGT7cnSKg4hbLFNg0NRDsavB1DY1mxoZBA90XlDZATctxDZBy9';
        $account->save();

        $role_setup = RoleSetupTemplate::whereRoleId(RoleTypeEnum::ADMINISTRATOR())->first();

        /**
         *  Setup DS users
         */
        foreach ($this->initDefaultUsers() as $defaultUser) {
            $user = new User();
            $user->first_name = $defaultUser['first_name'];
            $user->last_name = $defaultUser['last_name'];
            $user->email = $defaultUser['email'];
            $user->password = Hash::make('password');
            $user->email_verified_at = now();
            $user->remember_token = Str::random(10);
            $user->is_owner = true;
            $user->role_id = $role_setup->role_id;
            $user->role_setup = $role_setup->setup;
            $user->account_id = $account->id;
            $user->save();
        }
    }

    protected function initDefaultUsers()
    {
        return [
            [
                'first_name' => 'Evan',
                'last_name' => 'Disrupt',
                'email' => 'e@disruptsocial.net',
            ],
            [
                'first_name' => 'Brylle',
                'last_name' => 'Disrupt',
                'email' => 'brylle@disruptsocial.net',
            ],
            [
                'first_name' => 'Abz',
                'last_name' => 'Disrupt',
                'email' => 'abz@disruptsocial.net',
            ],
            [
                'first_name' => 'Justin',
                'last_name' => 'Disrupt',
                'email' => 'justin@disruptsocial.net',
            ],
            [
                'first_name' => 'Jim',
                'last_name' => 'Disrupt',
                'email' => 'jim@disruptsocial.net',
            ],
            [
                'first_name' => 'Kim',
                'last_name' => 'Disrupt',
                'email' => 'kim@disruptsocial.net',
            ],
            [
                'first_name' => 'Oleg',
                'last_name' => 'Disrupt',
                'email' => 'oleg@disruptsocial.net',
            ],
            [
                'first_name' => 'Ronald',
                'last_name' => 'Disrupt',
                'email' => 'ronald@disruptsocial.net',
            ],
            [
                'first_name' => 'Christa',
                'last_name' => 'Disrupt',
                'email' => 'christa@disruptsocial.net',
            ],
            [
                'first_name' => 'Ed',
                'last_name' => 'Disrupt',
                'email' => 'ed@disrupt.social',
            ],
        ];
    }
}

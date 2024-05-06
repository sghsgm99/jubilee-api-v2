<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        foreach ($users as $user) {
            $setting = new Setting();
            $setting->user_id = $user->id;
            $setting->account_id = $user->account_id;
            $setting->save();
        }
    }
}

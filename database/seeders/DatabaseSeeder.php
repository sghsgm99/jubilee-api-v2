<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
       $this->call(RoleSetupTemplateSeeder::class);
       $this->call(BaseSeeder::class);
       $this->call(AccountSeeder::class);
       $this->call(UserSeeder::class);
       $this->call(SettingSeeder::class);
    }
}

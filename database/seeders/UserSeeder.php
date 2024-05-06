<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create users for the other account
        $account = Account::all()->last();
        User::factory(User::class)
            ->for($account)
            ->count(3)
            ->create();
    }
}

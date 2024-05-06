<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Site;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereAccountId(1)->get()->random();
        Site::factory(Site::class)
            ->for($user)
            ->for($user->account)
            ->count(8)
            ->create();

        $user = User::whereAccountId(1)->get()->random();
        Site::factory(Site::class)
            ->for($user)
            ->for($user->account)
            ->count(7)
            ->create();

        $user = User::whereAccountId(2)->get()->random();
        Site::factory(Site::class)
            ->for($user)
            ->for($user->account)
            ->count(6)
            ->create();
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Channel;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereAccountId(1)->get()->random();
        Channel::factory(Channel::class)
            ->for($user)
            ->for($user->account)
            ->count(6)
            ->create();

        $user = User::whereAccountId(1)->get()->random();
        Channel::factory(Channel::class)
            ->for($user)
            ->for($user->account)
            ->count(5)
            ->create();

        $user = User::whereAccountId(2)->get()->random();
        Channel::factory(Channel::class)
            ->for($user)
            ->for($user->account)
            ->count(5)
            ->create();
    }
}

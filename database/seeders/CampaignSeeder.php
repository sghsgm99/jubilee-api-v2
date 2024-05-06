<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Campaign;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereAccountId(1)->get()->random();
        Campaign::factory(Campaign::class)
            ->for($user)
            ->for($user->account)
            ->for(Article::whereAccountId(1)->get()->random())
            ->for(Site::whereAccountId(1)->get()->random())
            ->for(Channel::whereAccountId(1)->get()->random())
            ->count(6)
            ->create();

        $user = User::whereAccountId(2)->get()->random();
        Campaign::factory(Campaign::class)
            ->for($user)
            ->for($user->account)
            ->for(Article::whereAccountId(2)->get()->random())
            ->for(Site::whereAccountId(2)->get()->random())
            ->for(Channel::whereAccountId(2)->get()->random())
            ->count(6)
            ->create();

        $user = User::whereAccountId(1)->get()->random();
        Campaign::factory(Campaign::class)
            ->for($user)
            ->for($user->account)
            ->for(Article::whereAccountId(1)->get()->random())
            ->for(Site::whereAccountId(1)->get()->random())
            ->for(Channel::whereAccountId(1)->get()->random())
            ->count(3)
            ->create();
    }
}

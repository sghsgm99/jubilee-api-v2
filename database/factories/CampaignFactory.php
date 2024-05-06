<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use App\Models\Enums\CampaignStatusEnum;
use App\Models\User;
use App\Models\Account;
use App\Models\Channel;
use App\Models\Campaign;
use App\Models\Site;
use App\Models\Article;

class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = CampaignStatusEnum::members();
        $user = User::all()->random();

        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->sentences(3,5),
            'article_id' => Article::all()->random()->id,
            'site_id' => Site::all()->random()->id,
            'channel_id' => Channel::all()->random()->id,
            'channel_api_preferences' => null,
            'user_id' => $user->id,
            'account_id' => $user->account->id,
            'status' => Arr::random($status)
        ];
    }
}

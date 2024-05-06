<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\User;
use App\Models\Account;
use App\Models\Channel;

class ChannelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Channel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = ChannelStatusEnum::members();
        $user = User::all()->random();

        return [
            'title' => $this->faker->sentence(),
            'content' => $this->faker->sentences(2,3),
            'api_key' => $this->faker->md5,
            'api_callback' => $this->faker->sha1,
            'api_permissions' => $this->faker->sha1,
            'user_id' => $user->id,
            'account_id' => $user->account->id,
            'status' =>  Arr::random($status),
            'platform' => ChannelPlatformEnum::FACEBOOK
        ];
    }
}

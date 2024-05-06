<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Enums\SiteStatusEnum;
use App\Models\User;
use App\Models\Account;
use App\Models\Enums\SitePlatformEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class SiteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Site::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = SiteStatusEnum::members();
        $platform = SitePlatformEnum::members();
        $user = User::all()->random();

        $prefix = 'jubilee_';
        $key = md5($this->faker->name().Carbon::now());
        $api_jubilee_key = $prefix.$key;

        return [
            'name' => $this->faker->name(),
            'url' => $this->faker->url(),
            'api_callback' => $this->faker->sha1,
            'api_jubilee_key' => $api_jubilee_key,
            'api_permissions' => $this->faker->sha1,
            'platform' => Arr::random($platform),
            'status' => Arr::random($status),            
            'description' => $this->faker->sentence(),
            'user_id' => $user->id,
            'account_id' => $user->account->id,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use App\Models\Enums\GenericStatusEnum;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\User;

class SiteSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SiteSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = GenericStatusEnum::members();
        $user = User::all()->random();
        $site = Site::all()->random();

        /**
         * title
         * description
         * theme_id
         * about_us_blurb
         * contact_us_blurb
         * status
         */
        return [
            'title' => ucwords($this->faker->words(2,3)),
            'description' => $this->faker->sentences(2,3),
            'theme_id' => 1,
            'about_us_blurb' => $this->faker->sentences(2,3),
            'contact_us_blurb' => $this->faker->sentences(2,3),
            'status' => Arr::random($status),
            'site_id' => $site->id,
            // 'user_id' => $user->id,
            'account_id' => $user->account->id,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use App\Models\Enums\GenericStatusEnum;
use App\Models\Site;
use App\Models\SiteMenu;
use App\Models\User;

class SiteMenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SiteMenu::class;

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
         * slug
         * description
         * sort
         * status
         * site_id
         * account_id
         */
        return [
            'title' => ucwords($this->faker->words(2,3)),
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentences(2,3),
            'sort' => rand(1,10),
            'status' => Arr::random($status),
            'site_id' => $site->id,
            // 'user_id' => $user->id,
            'account_id' => $user->account->id,
        ];
    }
}

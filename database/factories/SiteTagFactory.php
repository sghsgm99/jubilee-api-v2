<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use App\Models\Enums\GenericStatusEnum;
use App\Models\Site;
use App\Models\SiteTag;
use App\Models\User;

class SiteTagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SiteTag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::all()->random();
        $site = Site::all()->random();

        return [
            'label' => strtolower($this->faker->word(1)),
            'tag_id' => $this->faker->unique()->numberBetween(0, 999),
            'site_id' => $site->id,
            'account_id' => $user->account->id,
        ];
    }
}

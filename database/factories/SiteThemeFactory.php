<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use App\Models\Enums\GenericStatusEnum;
use App\Models\Site;
use App\Models\SiteTheme;
use App\Models\User;

class SiteThemeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SiteTheme::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => 'default',
            'description' => $this->faker->sentences(2,3),
            'handle' => '/sample/theme/default',
            'status' => 1,
        ];
    }
}

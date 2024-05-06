<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = ArticleStatusEnum::members();
        $user = User::all()->random();

        $title = $this->faker->sentence(3);
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->faker->sentences(2,3),
            'user_id' => $user->id,
            'account_id' => $user->account->id,
            'status' => Arr::random($status)
        ];
    }
}

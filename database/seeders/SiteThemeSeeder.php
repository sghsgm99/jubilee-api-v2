<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteTheme;

class SiteThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->initDefaultTheme() as $theme) {
            $sitetime = new SiteTheme();
            $sitetime->title = $theme['title'];
            $sitetime->handle = $theme['handle'];
            $sitetime->status = $theme['status'];
            $sitetime->save();
        }
    }

    protected function initDefaultTheme()
    {
        return [
            [
                'title' => 'default',
                'handle' => '/sample/theme/default',
                'status' => 1,
            ],
            [
                'title' => 'Theme 1',
                'handle' => 'theme-1',
                'status' => 1,
            ],
            [
                'title' => 'Theme 2',
                'handle' => 'theme-2',
                'status' => 1,
            ],
            [
                'title' => 'Theme 3',
                'handle' => 'theme-3',
                'status' => 1,
            ],
            [
                'title' => 'Theme 4',
                'handle' => 'theme-4',
                'status' => 1,
            ],
            [
                'title' => 'Theme 5',
                'handle' => 'theme-5',
                'status' => 1,
            ],
            [
                'title' => 'Theme 6',
                'handle' => 'theme-6',
                'status' => 1,
            ],
            [
                'title' => 'Theme 7',
                'handle' => 'theme-7',
                'status' => 1,
            ],
            [
                'title' => 'Theme 8',
                'handle' => 'theme-8',
                'status' => 1,
            ],
            [
                'title' => 'Theme 9',
                'handle' => 'theme-9',
                'status' => 1,
            ],
            [
                'title' => 'Theme 10',
                'handle' => 'theme-10',
                'status' => 1,
            ],
            [
                'title' => 'Theme 11',
                'handle' => 'theme-11',
                'status' => 1,
            ]
        ];
    }
}

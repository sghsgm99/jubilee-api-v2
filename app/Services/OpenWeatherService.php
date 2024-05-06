<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenWeatherService
{
    protected $api_key = null;

    public static function resolve(): OpenWeatherService
    {
        /** @var OpenWeatherService $self */
        $self = app(static::class);
        $self->api_key = config('openWeather.api_key');

        if (!$self->api_key) {
            throw new \InvalidArgumentException("Missing OpenWeather API key");
        }

        return $self;
    }

    public function getGeography(
        string $location,
        int $limit = 10
    )
    {
        $api = Http::get('https://api.openweathermap.org/geo/1.0/direct', [
            'q' => $location,
            'limit' => $limit,
            'appid' => $this->api_key
        ]);

        return $api->json();
    }

    public function getWeather(
        float $lat,
        float $lon,
        string $unit = 'imperial'
    )
    {
        $api = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->api_key,
            'units' => $unit
        ]);

        return $api->json();
    }
}

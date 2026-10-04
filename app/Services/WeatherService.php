<?php

namespace App\Services;

use App\Exceptions\WeatherException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function current(string $city): array
    {
        $apiKey = config('weather.api_key');

        if (! $apiKey) {
            throw WeatherException::notConfigured();
        }

        try {
            $response = Http::baseUrl(config('weather.base_url'))
                ->timeout(config('weather.timeout'))
                ->acceptJson()
                ->get('weather', [
                    'q' => $city,
                    'appid' => $apiKey,
                    'units' => 'metric',
                ]);
        } catch (ConnectionException $e) {
            throw str_contains($e->getMessage(), 'timed out')
                ? WeatherException::timeout()
                : WeatherException::unavailable();
        }

        if ($response->status() === 404) {
            throw WeatherException::cityNotFound($city);
        }

        if ($response->failed() || $response->json('main.temp') === null) {
            throw WeatherException::unavailable();
        }

        return [
            'city' => $response->json('name', $city),
            'temperature' => round($response->json('main.temp'), 1),
            'weather_description' => $response->json('weather.0.description'),
            'timestamp' => Carbon::createFromTimestampUTC($response->json('dt', time()))
                ->toIso8601ZuluString(),
        ];
    }
}

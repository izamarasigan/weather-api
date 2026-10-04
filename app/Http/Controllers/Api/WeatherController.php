<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\WeatherException;
use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WeatherController extends Controller
{
    public function __construct(private WeatherService $weather)
    {
    }

    public function show(string $city): JsonResponse
    {
        $city = $this->cleanCity($city);

        return response()->json($this->weather->current($city) + ['source' => 'external']);
    }

    public function cached(string $city): JsonResponse
    {
        $city = $this->cleanCity($city);
        $key = 'weather:' . Str::lower($city);

        if ($data = Cache::get($key)) {
            return response()->json($data + ['source' => 'cache']);
        }

        $data = $this->weather->current($city);
        Cache::put($key, $data, now()->addMinutes(config('weather.cache_minutes')));

        return response()->json($data + ['source' => 'external']);
    }

    private function cleanCity(string $city): string
    {
        $city = trim($city);

        // letters, spaces, dots, apostrophes and hyphens only (e.g. "Quezon City", "Cagayan de Oro")
        if (! preg_match("/^[\p{L}\s.'-]{1,100}$/u", $city)) {
            throw WeatherException::invalidCity();
        }

        return $city;
    }
}

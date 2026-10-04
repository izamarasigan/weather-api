<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['weather.api_key' => 'test-key']);
    }

    private function fakeOpenWeather(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'name' => 'Manila',
                'dt' => 1790850600,
                'main' => ['temp' => 30.46],
                'weather' => [['description' => 'scattered clouds']],
            ]),
        ]);
    }

    public function test_it_returns_current_weather_from_the_external_api(): void
    {
        $this->fakeOpenWeather();

        $this->getJson('/api/weather/Manila')
            ->assertOk()
            ->assertJson([
                'city' => 'Manila',
                'temperature' => 30.5,
                'weather_description' => 'scattered clouds',
                'source' => 'external',
            ]);
    }

    public function test_cached_endpoint_only_calls_the_external_api_once(): void
    {
        $this->fakeOpenWeather();

        $this->getJson('/api/weather/Manila/cached')
            ->assertOk()
            ->assertJsonPath('source', 'external');

        $this->getJson('/api/weather/Manila/cached')
            ->assertOk()
            ->assertJsonPath('source', 'cache')
            ->assertJsonPath('city', 'Manila');

        Http::assertSentCount(1);
    }

    public function test_unknown_city_returns_404(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response(['cod' => '404', 'message' => 'city not found'], 404),
        ]);

        $this->getJson('/api/weather/Nowhereville')
            ->assertNotFound()
            ->assertJsonPath('message', "City 'Nowhereville' was not found.");
    }

    public function test_invalid_city_name_is_rejected_without_calling_the_api(): void
    {
        Http::fake();

        $this->getJson('/api/weather/Manila123')->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_external_api_timeout_returns_504(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->getJson('/api/weather/Manila')
            ->assertStatus(504)
            ->assertJsonPath('message', 'The weather service took too long to respond.');
    }

    public function test_external_api_failure_returns_502(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response([], 500)]);

        $this->getJson('/api/weather/Manila')->assertStatus(502);
    }
}

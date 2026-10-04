<?php

namespace Tests\Feature;

use App\Models\WeatherRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeatherHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_by_city_and_date_range(): void
    {
        WeatherRecord::factory()->create(['city' => 'Manila', 'recorded_at' => '2026-03-01 10:00:00']);
        WeatherRecord::factory()->create(['city' => 'Manila', 'recorded_at' => '2026-06-30 23:00:00']);
        WeatherRecord::factory()->create(['city' => 'Manila', 'recorded_at' => '2025-12-31 10:00:00']);
        WeatherRecord::factory()->create(['city' => 'Cebu', 'recorded_at' => '2026-03-01 10:00:00']);

        $response = $this->getJson('/api/weather/history?city=Manila&from=2026-01-01&to=2026-06-30');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.created_at');

        $cities = collect($response->json('data'))->pluck('city')->unique()->all();
        $this->assertSame(['Manila'], $cities);
    }

    public function test_it_paginates_results(): void
    {
        WeatherRecord::factory()->count(25)->create(['city' => 'Davao']);

        $this->getJson('/api/weather/history?per_page=10&page=3')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_it_rejects_a_date_range_that_ends_before_it_starts(): void
    {
        $this->getJson('/api/weather/history?from=2026-06-30&to=2026-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    }

    public function test_it_rejects_bad_pagination_values(): void
    {
        $this->getJson('/api/weather/history?per_page=1000&page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page', 'page']);
    }
}

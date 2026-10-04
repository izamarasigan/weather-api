<?php

namespace Database\Factories;

use App\Models\WeatherRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeatherRecord>
 */
class WeatherRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'city' => fake()->randomElement([
                'Manila', 'Cebu', 'Davao', 'Makati', 'Taguig', 'Quezon City',
            ]),
            'temperature' => fake()->randomFloat(1, 24, 35),
            'weather_description' => fake()->randomElement([
                'clear sky', 'few clouds', 'scattered clouds', 'broken clouds',
                'overcast clouds', 'light rain', 'moderate rain', 'thunderstorm',
            ]),
            'recorded_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

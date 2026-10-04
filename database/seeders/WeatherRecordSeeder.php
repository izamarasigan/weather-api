<?php

namespace Database\Seeders;

use App\Models\WeatherRecord;
use Illuminate\Database\Seeder;

class WeatherRecordSeeder extends Seeder
{
    public function run(): void
    {
        // 10 x 1,000 = 10,000 rows. Bulk inserting in chunks is a lot faster than
        // calling create() 10,000 times and keeps memory flat.
        for ($i = 0; $i < 10; $i++) {
            $rows = WeatherRecord::factory()
                ->count(1000)
                ->make()
                ->map(fn (WeatherRecord $record) => $record->getAttributes())
                ->all();

            WeatherRecord::insert($rows);
        }
    }
}

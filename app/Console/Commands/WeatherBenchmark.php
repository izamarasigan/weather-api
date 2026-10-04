<?php

namespace App\Console\Commands;

use App\Models\WeatherRecord;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WeatherBenchmark extends Command
{
    protected $signature = 'weather:benchmark {--city=Manila} {--runs=20}';

    protected $description = 'Benchmark the historical weather query with and without the index';

    public function handle(): int
    {
        $city = $this->option('city');
        $runs = max(1, (int) $this->option('runs'));
        $from = now()->subMonths(6)->startOfDay();
        $to = now()->endOfDay();

        $total = WeatherRecord::count();

        if ($total === 0) {
            $this->error('No records found. Run php artisan migrate:fresh --seed first.');

            return self::FAILURE;
        }

        $matching = WeatherRecord::inCity($city)->recordedBetween($from, $to)->count();

        $this->line("Dataset size:     {$total} records");
        $this->line("Query:            {$city}, {$from->toDateString()} to {$to->toDateString()}");
        $this->line("Matching records: {$matching}");
        $this->line("Runs per test:    {$runs} (after one warm-up run)");
        $this->newLine();

        $paged = fn () => WeatherRecord::query()
            ->select(['id', 'city', 'temperature', 'weather_description', 'recorded_at'])
            ->inCity($city)
            ->recordedBetween($from, $to)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(50);

        $everything = fn () => WeatherRecord::inCity($city)
            ->recordedBetween($from, $to)
            ->orderByDesc('recorded_at')
            ->get();

        $withIndex = $this->measure($paged, $runs);
        $loadAll = $this->measure($everything, $runs);

        // Drop the index, measure again, and always put it back afterwards
        Schema::table('weather_records', fn (Blueprint $table) => $table->dropIndex(['city', 'recorded_at']));

        try {
            $withoutIndex = $this->measure($paged, $runs);
        } finally {
            Schema::table('weather_records', fn (Blueprint $table) => $table->index(['city', 'recorded_at']));
        }

        $this->table(['Scenario', 'Avg time (ms)', 'Memory (MB)'], [
            ['Paginated (50), no index', ...$this->format($withoutIndex)],
            ['Paginated (50), with index', ...$this->format($withIndex)],
            ['Load all matching rows (get)', ...$this->format($loadAll)],
        ]);

        return self::SUCCESS;
    }

    private function measure(callable $query, int $runs): array
    {
        $query(); // warm-up so the first run doesn't skew the average

        $times = [];

        for ($i = 0; $i < $runs; $i++) {
            $start = hrtime(true);
            $result = $query();
            $times[] = (hrtime(true) - $start) / 1_000_000;
        }

        unset($result);
        gc_collect_cycles();

        $before = memory_get_usage();
        $result = $query();
        $memory = memory_get_usage() - $before;

        return [array_sum($times) / count($times), $memory];
    }

    private function format(array $result): array
    {
        return [number_format($result[0], 2), number_format($result[1] / 1048576, 2)];
    }
}

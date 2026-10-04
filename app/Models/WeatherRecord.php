<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WeatherRecord extends Model
{
    use HasFactory;

    protected $fillable = ['city', 'temperature', 'weather_description', 'recorded_at'];

    protected $casts = [
        'temperature' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function scopeInCity(Builder $query, ?string $city): void
    {
        if ($city) {
            $query->where('city', $city);
        }
    }

    // $from and $to can be dates, strings or null. A plain "to" date includes that whole day.
    public function scopeRecordedBetween(Builder $query, $from, $to): void
    {
        if ($from) {
            $query->where('recorded_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to) {
            $query->where('recorded_at', '<=', Carbon::parse($to)->endOfDay());
        }
    }
}

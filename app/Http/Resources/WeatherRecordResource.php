<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'city' => $this->city,
            'temperature' => $this->temperature,
            'weather_description' => $this->weather_description,
            'recorded_at' => $this->recorded_at->toIso8601ZuluString(),
        ];
    }
}

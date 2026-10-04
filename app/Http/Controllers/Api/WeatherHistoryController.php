<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HistoryRequest;
use App\Http\Resources\WeatherRecordResource;
use App\Models\WeatherRecord;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WeatherHistoryController extends Controller
{
    public function index(HistoryRequest $request): AnonymousResourceCollection
    {
        $records = WeatherRecord::query()
            ->select(['id', 'city', 'temperature', 'weather_description', 'recorded_at'])
            ->inCity($request->input('city'))
            ->recordedBetween($request->input('from'), $request->input('to'))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return WeatherRecordResource::collection($records);
    }
}

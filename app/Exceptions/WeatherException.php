<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class WeatherException extends RuntimeException
{
    public function __construct(string $message, private int $status)
    {
        parent::__construct($message);
    }

    public static function invalidCity(): self
    {
        return new self('The city name is invalid.', 422);
    }

    public static function cityNotFound(string $city): self
    {
        return new self("City '{$city}' was not found.", 404);
    }

    public static function timeout(): self
    {
        return new self('The weather service took too long to respond.', 504);
    }

    public static function unavailable(): self
    {
        return new self('The weather service is currently unavailable.', 502);
    }

    public static function notConfigured(): self
    {
        return new self('The weather service is not configured.', 503);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}

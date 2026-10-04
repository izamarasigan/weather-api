<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_records', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->decimal('temperature', 4, 1);
            $table->string('weather_description');
            $table->timestamp('recorded_at');
            $table->timestamps();

            // The history endpoint always filters by city and/or a date range and sorts by date
            $table->index(['city', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_records');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemon_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->string('stat_name');
            $table->integer('base_stat');
            $table->integer('effort');
            $table->timestamps();

            // Covering index for efficient stat sorting in Filament tables
            $table->index(['pokemon_id', 'stat_name', 'base_stat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_stats');
    }
};

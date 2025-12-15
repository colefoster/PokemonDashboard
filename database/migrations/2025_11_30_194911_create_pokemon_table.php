<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemon', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('base_experience')->nullable();
            $table->boolean('is_default')->default(true);
            $table->foreignId('species_id')->nullable()->constrained('pokemon_species')->nullOnDelete();

            // Sprite URLs
            $table->string('sprite_front_default')->nullable();
            $table->string('sprite_front_shiny')->nullable();
            $table->string('sprite_back_default')->nullable();
            $table->string('sprite_back_shiny')->nullable();

            // Cries URLs
            $table->string('cry_latest')->nullable();
            $table->string('cry_legacy')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('api_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokemon_species', function (Blueprint $table) {
            $table->id();
            $table->integer('api_id')->unique();
            $table->string('name');
            $table->integer('base_happiness')->nullable();
            $table->integer('capture_rate')->nullable();
            $table->string('color')->nullable();
            $table->integer('gender_rate')->nullable();
            $table->integer('hatch_counter')->nullable();
            $table->boolean('is_baby')->default(false);
            $table->boolean('is_legendary')->default(false);
            $table->boolean('is_mythical')->default(false);
            $table->string('habitat')->nullable();
            $table->string('shape')->nullable();
            $table->string('generation')->nullable();
            $table->foreignId('evolution_chain_id')->nullable()->constrained('evolution_chains')->nullOnDelete();
            $table->timestamps();

            $table->index('api_id');
            $table->index('is_legendary');
            $table->index('is_mythical');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_species');
    }
};

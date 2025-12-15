<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evolution_chain_id')->constrained('evolution_chains')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('pokemon_species')->cascadeOnDelete();
            $table->foreignId('evolves_to_species_id')->nullable()->constrained('pokemon_species')->cascadeOnDelete();
            $table->string('trigger')->nullable();
            $table->integer('min_level')->nullable();
            $table->string('item')->nullable();
            $table->string('held_item')->nullable();
            $table->string('gender')->nullable();
            $table->integer('min_happiness')->nullable();
            $table->integer('min_beauty')->nullable();
            $table->integer('min_affection')->nullable();
            $table->string('location')->nullable();
            $table->string('time_of_day')->nullable();
            $table->string('known_move')->nullable();
            $table->string('known_move_type')->nullable();
            $table->string('party_species')->nullable();
            $table->string('party_type')->nullable();
            $table->integer('relative_physical_stats')->nullable();
            $table->boolean('needs_overworld_rain')->default(false);
            $table->string('trade_species')->nullable();
            $table->boolean('turn_upside_down')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['species_id', 'evolves_to_species_id']);
            $table->index('evolution_chain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolutions');
    }
};

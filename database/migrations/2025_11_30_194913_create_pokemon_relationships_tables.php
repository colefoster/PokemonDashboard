<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pokemon Types pivot table
        Schema::create('pokemon_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->foreignId('type_id')->constrained('types')->cascadeOnDelete();
            $table->integer('slot')->default(1);
            $table->timestamps();

            $table->unique(['pokemon_id', 'type_id']);
        });

        // Pokemon Abilities pivot table
        Schema::create('ability_pokemon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->foreignId('ability_id')->constrained('abilities')->cascadeOnDelete();
            $table->boolean('is_hidden')->default(false);
            $table->integer('slot')->default(1);
            $table->timestamps();

            $table->unique(['pokemon_id', 'ability_id']);
            $table->index('ability_id'); // Optimize queries for finding all pokemon with a specific ability
        });

        // Pokemon Moves pivot table
        Schema::create('move_pokemon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->foreignId('move_id')->constrained('moves')->cascadeOnDelete();
            $table->string('learn_method')->nullable();
            $table->integer('level_learned_at')->nullable();
            $table->timestamps();

            $table->index(['pokemon_id', 'move_id']);
            $table->index('move_id'); // Optimize queries for finding all pokemon that can learn a specific move
        });

        // Pokemon Items pivot table (held items)
        Schema::create('pokemon_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('rarity')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();

            $table->index(['pokemon_id', 'item_id']);
        });

        // Pokemon Game Indices table
        Schema::create('pokemon_game_indices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->integer('game_index');
            $table->string('version');
            $table->timestamps();

            $table->index(['pokemon_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokemon_game_indices');
        Schema::dropIfExists('pokemon_item');
        Schema::dropIfExists('move_pokemon');
        Schema::dropIfExists('ability_pokemon');
        Schema::dropIfExists('pokemon_type');
    }
};

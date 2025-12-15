<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pokemon_type', function (Blueprint $table) {
            // Covering index for loading types per Pokemon with slot ordering
            // Optimizes: SELECT * FROM pokemon_type WHERE pokemon_id IN (...) ORDER BY slot
            $table->index(['pokemon_id', 'slot'], 'pokemon_type_pokemon_slot_index');

            // Index for reverse lookups (finding all Pokemon with a specific type)
            // Optimizes: SELECT * FROM pokemon_type WHERE type_id = ?
            $table->index('type_id', 'pokemon_type_type_id_index');
        });

        Schema::table('types', function (Blueprint $table) {
            // Index for type name lookups and color resolution
            // Optimizes: SELECT * FROM types WHERE name = ?
            $table->index('name', 'types_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pokemon_type', function (Blueprint $table) {
            $table->dropIndex('pokemon_type_pokemon_slot_index');
            $table->dropIndex('pokemon_type_type_id_index');
        });

        Schema::table('types', function (Blueprint $table) {
            $table->dropIndex('types_name_index');
        });
    }
};

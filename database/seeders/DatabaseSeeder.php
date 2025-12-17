<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed users first
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_admin' => true,
        ]);

        // Seed Pokemon data from PokeAPI in the correct order
        // Order matters due to foreign key dependencies
        $this->call([
            TypeSeeder::class,           // Must be first (Types are referenced by Moves and Pokemon)
            AbilitySeeder::class,        // Can run after Types
            MoveSeeder::class,           // Depends on Types
            ItemSeeder::class,           // Can run independently
            PokemonSpeciesSeeder::class, // Must be before Pokemon and EvolutionChains
            EvolutionChainSeeder::class, // Depends on PokemonSpecies
            PokemonSeeder::class,        // Depends on Types, Abilities, Moves, Items, and PokemonSpecies
        ]);
    }
}


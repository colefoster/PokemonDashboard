<?php

namespace Database\Seeders;

use App\Models\PokemonSpecies;
use App\Services\PokeApiService;
use Illuminate\Database\Seeder;

class PokemonSpeciesSeeder extends Seeder
{
    protected PokeApiService $api;

    public function __construct()
    {
        $this->api = app(PokeApiService::class);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧬 Importing Pokemon Species...');

        try {
            $offset = 0;
            $limit = 100;

            do {
                $response = $this->api->fetch("/pokemon-species?limit={$limit}&offset={$offset}");
                $speciesList = $response['results'] ?? [];

                if (empty($speciesList)) {
                    break;
                }

                $bar = $this->command->getOutput()->createProgressBar(count($speciesList));
                $bar->start();

                foreach ($speciesList as $speciesData) {
                    try {
                        $speciesId = $this->api->extractIdFromUrl($speciesData['url']);
                        $speciesDetails = $this->api->fetch("/pokemon-species/{$speciesId}");

                        PokemonSpecies::updateOrCreate(
                            ['api_id' => $speciesDetails['id']],
                            [
                                'name' => $speciesDetails['name'],
                                'base_happiness' => $speciesDetails['base_happiness'],
                                'capture_rate' => $speciesDetails['capture_rate'],
                                'color' => $speciesDetails['color']['name'] ?? null,
                                'gender_rate' => $speciesDetails['gender_rate'],
                                'hatch_counter' => $speciesDetails['hatch_counter'],
                                'is_baby' => $speciesDetails['is_baby'] ?? false,
                                'is_legendary' => $speciesDetails['is_legendary'] ?? false,
                                'is_mythical' => $speciesDetails['is_mythical'] ?? false,
                                'habitat' => $speciesDetails['habitat']['name'] ?? null,
                                'shape' => $speciesDetails['shape']['name'] ?? null,
                                'generation' => $speciesDetails['generation']['name'] ?? null,
                            ]
                        );

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing species: " . $e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($speciesList));

            $this->command->info("Pokemon Species imported: " . PokemonSpecies::count());
        } catch (\Exception $e) {
            $this->command->error('❌ Pokemon Species import failed: ' . $e->getMessage());
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Evolution;
use App\Models\EvolutionChain;
use App\Models\PokemonSpecies;
use App\Services\PokeApiService;
use App\Services\SeederProgressService;
use Illuminate\Database\Seeder;

class EvolutionChainSeeder extends Seeder
{
    protected PokeApiService $api;
    protected SeederProgressService $progress;

    public function __construct()
    {
        $this->api = app(PokeApiService::class);
        $this->progress = app(SeederProgressService::class);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔗 Importing Evolution Chains...');

        try {
            $offset = 0;
            $limit = 100;

            // Get total count first
            $initialResponse = $this->api->fetch("/evolution-chain?limit=1&offset=0");
            $totalCount = $initialResponse['count'] ?? 0;

            $this->progress->start('evolution_chains', $totalCount);

            do {
                $response = $this->api->fetch("/evolution-chain?limit={$limit}&offset={$offset}");
                $chains = $response['results'] ?? [];

                if (empty($chains)) {
                    break;
                }

                $bar = $this->command->getOutput()->createProgressBar(count($chains));
                $bar->start();

                foreach ($chains as $chainData) {
                    try {
                        $chainId = $this->api->extractIdFromUrl($chainData['url']);
                        $chainDetails = $this->api->fetch("/evolution-chain/{$chainId}");

                        $evolutionChain = EvolutionChain::updateOrCreate(
                            ['api_id' => $chainDetails['id']],
                            ['baby_trigger_item' => $chainDetails['baby_trigger_item']['name'] ?? null]
                        );

                        $this->parseEvolutionChain($evolutionChain, $chainDetails['chain']);

                        $this->progress->advance("Importing evolution chain #{$chainId}");
                        $this->progress->success();

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing evolution chain: " . $e->getMessage());
                        $this->progress->error($e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($chains));

            $this->command->info("Evolution Chains imported: " . EvolutionChain::count());
            $this->progress->complete('evolution_chains');
        } catch (\Exception $e) {
            $this->command->error('❌ Evolution Chain import failed: ' . $e->getMessage());
            $this->progress->error($e->getMessage());
        }
    }

    private function parseEvolutionChain(EvolutionChain $evolutionChain, array $chainNode, ?int $fromSpeciesId = null): void
    {
        $speciesName = $chainNode['species']['name'];
        $species = PokemonSpecies::where('name', $speciesName)->first();

        if (!$species) {
            return;
        }

        $species->update(['evolution_chain_id' => $evolutionChain->id]);

        if ($fromSpeciesId && isset($chainNode['evolution_details'][0])) {
            $details = $chainNode['evolution_details'][0];

            Evolution::updateOrCreate(
                [
                    'evolution_chain_id' => $evolutionChain->id,
                    'species_id' => $fromSpeciesId,
                    'evolves_to_species_id' => $species->id,
                ],
                [
                    'trigger' => $details['trigger']['name'] ?? null,
                    'min_level' => $details['min_level'] ?? null,
                    'item' => $details['item']['name'] ?? null,
                    'held_item' => $details['held_item']['name'] ?? null,
                    'gender' => $details['gender'] ?? null,
                    'min_happiness' => $details['min_happiness'] ?? null,
                    'min_beauty' => $details['min_beauty'] ?? null,
                    'min_affection' => $details['min_affection'] ?? null,
                    'location' => $details['location']['name'] ?? null,
                    'time_of_day' => $details['time_of_day'] ?? null,
                    'known_move' => $details['known_move']['name'] ?? null,
                    'known_move_type' => $details['known_move_type']['name'] ?? null,
                    'party_species' => $details['party_species']['name'] ?? null,
                    'party_type' => $details['party_type']['name'] ?? null,
                    'relative_physical_stats' => $details['relative_physical_stats'] ?? null,
                    'needs_overworld_rain' => $details['needs_overworld_rain'] ?? false,
                    'trade_species' => $details['trade_species']['name'] ?? null,
                    'turn_upside_down' => $details['turn_upside_down'] ?? false,
                ]
            );
        }

        foreach ($chainNode['evolves_to'] ?? [] as $evolution) {
            $this->parseEvolutionChain($evolutionChain, $evolution, $species->id);
        }
    }
}

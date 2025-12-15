<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\Evolution;
use App\Models\EvolutionChain;
use App\Models\PokemonSpecies;
use Illuminate\Console\Command;

class ImportEvolutionChains extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-evolution-chains
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Number of records to fetch per page}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon evolution chains from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->shouldRunParallel()) {
            return $this->runInParallel('/evolution-chain', 'pokemon:import-evolution-chains', [
                '--delay' => $this->option('delay'),
            ]);
        }

        $this->info('🔗 Importing Evolution Chains...');

        try {
            $offset = $this->getStartOffset();
            $limit = (int) $this->option('limit');
            $maxItems = $this->getMaxItems();
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/evolution-chain?limit={$limit}&offset={$offset}");
                $chains = $response['results'] ?? [];

                if (empty($chains) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $bar = $this->output->createProgressBar(count($chains));
                $bar->start();

                foreach ($chains as $chainData) {
                    if ($maxItems && $itemsProcessed >= $maxItems) break;

                    try {
                        $itemsProcessed++;
                        $chainId = $this->api->extractIdFromUrl($chainData['url']);
                        $chainDetails = $this->api->fetch("/evolution-chain/{$chainId}");

                        $evolutionChain = EvolutionChain::updateOrCreate(
                            ['api_id' => $chainDetails['id']],
                            ['baby_trigger_item' => $chainDetails['baby_trigger_item']['name'] ?? null]
                        );

                        $this->parseEvolutionChain($evolutionChain, $chainDetails['chain']);

                        $this->recordSuccess();
                        $bar->advance();
                        $this->applyDelay();
                    } catch (\Exception $e) {
                        $this->recordError();
                        $this->warn("\nError importing evolution chain: " . $e->getMessage());
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($chains) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->showStats('Evolution Chains', EvolutionChain::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
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

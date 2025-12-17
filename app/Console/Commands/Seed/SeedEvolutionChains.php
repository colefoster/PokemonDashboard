<?php

namespace App\Console\Commands\Seed;

use App\Models\Evolution;
use App\Models\EvolutionChain;
use App\Models\PokemonSpecies;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedEvolutionChains extends Command
{
    protected $signature = 'seed:evolution-chains
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}';

    protected $description = 'Seed evolution_chains table from PokeAPI with parallel processing support';

    protected PokeApiService $api;
    protected int $delay;

    public function handle(): int
    {
        $this->api = app(PokeApiService::class);
        $this->delay = (int) $this->option('delay');

        $threads = (int) $this->option('threads');
        $isWorker = $this->option('worker-id') !== null;

        if ($threads > 1 && !$isWorker) {
            return $this->runInParallel();
        }

        $prefix = $isWorker ? "[Worker {$this->option('worker-id')}] " : "";
        $this->info($prefix . '🔗 Importing Evolution Chains...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/evolution-chain?limit={$limit}&offset={$offset}");
                $chains = $response['results'] ?? [];

                if (empty($chains) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $remaining = $maxItems ? min(count($chains), $maxItems - $itemsProcessed) : count($chains);
                $bar = $this->output->createProgressBar($remaining);
                $bar->start();

                foreach (array_slice($chains, 0, $remaining) as $chainData) {
                    try {
                        $itemsProcessed++;
                        $chainId = $this->api->extractIdFromUrl($chainData['url']);
                        $chainDetails = $this->api->fetch("/evolution-chain/{$chainId}");

                        $evolutionChain = EvolutionChain::updateOrCreate(
                            ['api_id' => $chainDetails['id']],
                            ['baby_trigger_item' => $chainDetails['baby_trigger_item']['name'] ?? null]
                        );

                        $this->parseEvolutionChain($evolutionChain, $chainDetails['chain']);

                        $bar->advance();
                        usleep($this->delay * 1000);
                    } catch (\Exception $e) {
                        // Silent error handling
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($chains) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->info($prefix . "Evolution Chains imported: " . EvolutionChain::count());
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Evolution Chain import failed: ' . $e->getMessage());
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

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        $response = $this->api->fetch('/evolution-chain?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No evolution chains found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) break;

            $command = [
                PHP_BINARY, 'artisan', 'seed:evolution-chains',
                '--threads=1', '--worker-id=' . $i,
                '--offset=' . $offset, '--max-items=' . $maxItems,
                '--delay=' . $this->option('delay'),
                '--limit=' . $this->option('limit'),
            ];

            $process = new Process($command, base_path());
            $process->setTimeout(3600);
            $process->start();

            $processes[] = ['id' => $i, 'process' => $process];
            $this->info("[Worker {$i}] Started: offset={$offset}, items={$maxItems}");
        }

        $this->newLine();
        $this->info('⏳ Waiting for workers to complete...');

        $allSuccessful = true;
        foreach ($processes as $workerData) {
            $workerData['process']->wait();
            $success = $workerData['process']->isSuccessful();
            $this->info("[Worker {$workerData['id']}] " . ($success ? '✅ Completed' : '❌ Failed'));
            $allSuccessful = $allSuccessful && $success;
        }

        if ($allSuccessful) {
            $this->newLine();
            $this->info('✅ All workers completed successfully!');
            $this->info("Total evolution chains imported: " . EvolutionChain::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands\Seed;

use App\Models\PokemonSpecies;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedPokemonSpecies extends Command
{
    protected $signature = 'seed:pokemon-species
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}';

    protected $description = 'Seed pokemon_species table from PokeAPI with parallel processing support';

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
        $this->info($prefix . '🧬 Importing Pokemon Species...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/pokemon-species?limit={$limit}&offset={$offset}");
                $speciesList = $response['results'] ?? [];

                if (empty($speciesList) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $remaining = $maxItems ? min(count($speciesList), $maxItems - $itemsProcessed) : count($speciesList);
                $bar = $this->output->createProgressBar($remaining);
                $bar->start();

                foreach (array_slice($speciesList, 0, $remaining) as $speciesData) {
                    try {
                        $itemsProcessed++;
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
                        usleep($this->delay * 1000);
                    } catch (\Exception $e) {
                        // Silent error handling
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($speciesList) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->info($prefix . "Pokemon Species imported: " . PokemonSpecies::count());
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Pokemon Species import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        $response = $this->api->fetch('/pokemon-species?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No pokemon species found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) break;

            $command = [
                PHP_BINARY, 'artisan', 'seed:pokemon-species',
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
            $this->info("Total pokemon species imported: " . PokemonSpecies::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

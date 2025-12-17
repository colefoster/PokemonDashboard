<?php

namespace App\Console\Commands\Seed;

use App\Models\Ability;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedAbilities extends Command
{
    protected $signature = 'seed:abilities
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}';

    protected $description = 'Seed abilities table from PokeAPI with parallel processing support';

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
        $this->info($prefix . '⚡ Importing Abilities...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/ability?limit={$limit}&offset={$offset}");
                $abilities = $response['results'] ?? [];

                if (empty($abilities) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $remaining = $maxItems ? min(count($abilities), $maxItems - $itemsProcessed) : count($abilities);
                $bar = $this->output->createProgressBar($remaining);
                $bar->start();

                foreach (array_slice($abilities, 0, $remaining) as $abilityData) {
                    try {
                        $itemsProcessed++;
                        $abilityId = $this->api->extractIdFromUrl($abilityData['url']);
                        $abilityDetails = $this->api->fetch("/ability/{$abilityId}");

                        $effectEntry = $this->api->getEnglishEffect($abilityDetails['effect_entries'] ?? []);

                        Ability::updateOrCreate(
                            ['api_id' => $abilityDetails['id']],
                            [
                                'name' => $abilityDetails['name'],
                                'effect' => $effectEntry['effect'] ?? null,
                                'short_effect' => $effectEntry['short_effect'] ?? null,
                                'is_main_series' => $abilityDetails['is_main_series'] ?? true,
                            ]
                        );

                        $bar->advance();
                        usleep($this->delay * 1000);
                    } catch (\Exception $e) {
                        // Silent error handling in workers
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($abilities) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->info($prefix . "Abilities imported: " . Ability::count());
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Ability import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        $response = $this->api->fetch('/ability?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No abilities found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) break;

            $command = [
                PHP_BINARY, 'artisan', 'seed:abilities',
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
            $this->info("Total abilities imported: " . Ability::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

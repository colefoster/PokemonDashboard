<?php

namespace App\Console\Commands\Seed;

use App\Models\Type;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedTypes extends Command
{
    protected $signature = 'seed:types
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}';

    protected $description = 'Seed types table from PokeAPI with parallel processing support';

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
        $this->info($prefix . '📋 Importing Types...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;

            $response = $this->api->fetch("/type?limit={$limit}&offset={$offset}");
            $types = $response['results'] ?? [];

            if ($maxItems) {
                $types = array_slice($types, 0, $maxItems);
            }

            $bar = $this->output->createProgressBar(count($types));
            $bar->start();

            foreach ($types as $typeData) {
                try {
                    $typeId = $this->api->extractIdFromUrl($typeData['url']);
                    $typeDetails = $this->api->fetch("/type/{$typeId}");

                    Type::updateOrCreate(
                        ['api_id' => $typeDetails['id']],
                        ['name' => $typeDetails['name']]
                    );

                    $bar->advance();
                    usleep($this->delay * 1000);
                } catch (\Exception $e) {
                    $this->warn("\n{$prefix}Error importing type: " . $e->getMessage());
                }
            }

            $bar->finish();
            $this->newLine();
            $this->info($prefix . "Types imported: " . Type::count());

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Type import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        // Fetch total count
        $response = $this->api->fetch('/type?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No types found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        // Spawn worker processes
        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) {
                break;
            }

            $command = [
                PHP_BINARY,
                'artisan',
                'seed:types',
                '--threads=1',
                '--worker-id=' . $i,
                '--offset=' . $offset,
                '--max-items=' . $maxItems,
                '--delay=' . $this->option('delay'),
                '--limit=' . $this->option('limit'),
            ];

            $process = new Process($command, base_path());
            $process->setTimeout(3600);
            $process->start();

            $processes[] = ['id' => $i, 'process' => $process];
            $this->info("[Worker {$i}] Started: offset={$offset}, items={$maxItems}");
        }

        // Wait for completion
        $this->newLine();
        $this->info('⏳ Waiting for workers to complete...');

        $allSuccessful = true;
        foreach ($processes as $workerData) {
            $process = $workerData['process'];
            $workerId = $workerData['id'];

            $process->wait();

            if (!$process->isSuccessful()) {
                $this->error("[Worker {$workerId}] ❌ Failed");
                $allSuccessful = false;
            } else {
                $this->info("[Worker {$workerId}] ✅ Completed");
            }
        }

        if ($allSuccessful) {
            $this->newLine();
            $this->info('✅ All workers completed successfully!');
            $this->info("Total types imported: " . Type::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

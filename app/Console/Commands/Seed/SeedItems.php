<?php

namespace App\Console\Commands\Seed;

use App\Models\Item;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedItems extends Command
{
    protected $signature = 'seed:items
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}';

    protected $description = 'Seed items table from PokeAPI with parallel processing support';

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
        $this->info($prefix . '🎒 Importing Items...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/item?limit={$limit}&offset={$offset}");
                $items = $response['results'] ?? [];

                if (empty($items) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $remaining = $maxItems ? min(count($items), $maxItems - $itemsProcessed) : count($items);
                $bar = $this->output->createProgressBar($remaining);
                $bar->start();

                foreach (array_slice($items, 0, $remaining) as $itemData) {
                    try {
                        $itemsProcessed++;
                        $itemId = $this->api->extractIdFromUrl($itemData['url']);
                        $itemDetails = $this->api->fetch("/item/{$itemId}");

                        $effectEntry = $this->api->getEnglishEffect($itemDetails['effect_entries'] ?? []);
                        $flavorTextEntry = $this->api->getEnglishFlavorText($itemDetails['flavor_text_entries'] ?? []);

                        Item::updateOrCreate(
                            ['api_id' => $itemDetails['id']],
                            [
                                'name' => $itemDetails['name'],
                                'cost' => $itemDetails['cost'] ?? null,
                                'fling_power' => $itemDetails['fling_power'] ?? null,
                                'fling_effect' => $itemDetails['fling_effect']['name'] ?? null,
                                'category' => $itemDetails['category']['name'] ?? null,
                                'effect' => $effectEntry['effect'] ?? null,
                                'short_effect' => $effectEntry['short_effect'] ?? null,
                                'flavor_text' => $flavorTextEntry['text'] ?? null,
                                'sprite' => $itemDetails['sprites']['default'] ?? null,
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

            } while (!empty($items) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->info($prefix . "Items imported: " . Item::count());
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Item import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        $response = $this->api->fetch('/item?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No items found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) break;

            $command = [
                PHP_BINARY, 'artisan', 'seed:items',
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
            $this->info("Total items imported: " . Item::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

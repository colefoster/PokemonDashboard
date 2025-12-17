<?php

namespace App\Console\Commands\Seed;

use App\Models\Move;
use App\Models\Type;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedMoves extends Command
{
    protected $signature = 'seed:moves
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}';

    protected $description = 'Seed moves table from PokeAPI with parallel processing support';

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
        $this->info($prefix . '🥊 Importing Moves...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/move?limit={$limit}&offset={$offset}");
                $moves = $response['results'] ?? [];

                if (empty($moves) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $remaining = $maxItems ? min(count($moves), $maxItems - $itemsProcessed) : count($moves);
                $bar = $this->output->createProgressBar($remaining);
                $bar->start();

                foreach (array_slice($moves, 0, $remaining) as $moveData) {
                    try {
                        $itemsProcessed++;
                        $moveId = $this->api->extractIdFromUrl($moveData['url']);
                        $moveDetails = $this->api->fetch("/move/{$moveId}");

                        $typeId = null;
                        if (isset($moveDetails['type']['name'])) {
                            $type = Type::where('name', $moveDetails['type']['name'])->first();
                            $typeId = $type?->id;
                        }

                        $effectEntry = $this->api->getEnglishEffect($moveDetails['effect_entries'] ?? []);
                        $flavorTextEntry = $this->api->getEnglishFlavorText($moveDetails['flavor_text_entries'] ?? []);
                        $meta = $moveDetails['meta'] ?? [];

                        Move::updateOrCreate(
                            ['api_id' => $moveDetails['id']],
                            [
                                'name' => $moveDetails['name'],
                                'power' => $moveDetails['power'],
                                'pp' => $moveDetails['pp'],
                                'accuracy' => $moveDetails['accuracy'],
                                'priority' => $moveDetails['priority'],
                                'type_id' => $typeId,
                                'damage_class' => $moveDetails['damage_class']['name'] ?? null,
                                'effect_chance' => $moveDetails['effect_chance'] ?? null,
                                'contest_type' => $moveDetails['contest_type']['name'] ?? null,
                                'generation' => $moveDetails['generation']['name'] ?? null,
                                'effect' => $effectEntry['effect'] ?? null,
                                'short_effect' => $effectEntry['short_effect'] ?? null,
                                'flavor_text' => $flavorTextEntry['flavor_text'] ?? null,
                                'target' => $moveDetails['target']['name'] ?? null,
                                'ailment' => $meta['ailment']['name'] ?? null,
                                'meta_category' => $meta['category']['name'] ?? null,
                                'min_hits' => $meta['min_hits'] ?? null,
                                'max_hits' => $meta['max_hits'] ?? null,
                                'min_turns' => $meta['min_turns'] ?? null,
                                'max_turns' => $meta['max_turns'] ?? null,
                                'drain' => $meta['drain'] ?? null,
                                'healing' => $meta['healing'] ?? null,
                                'crit_rate' => $meta['crit_rate'] ?? null,
                                'ailment_chance' => $meta['ailment_chance'] ?? null,
                                'flinch_chance' => $meta['flinch_chance'] ?? null,
                                'stat_chance' => $meta['stat_chance'] ?? null,
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

            } while (!empty($moves) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->info($prefix . "Moves imported: " . Move::count());
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Move import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        $response = $this->api->fetch('/move?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No moves found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) break;

            $command = [
                PHP_BINARY, 'artisan', 'seed:moves',
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
            $this->info("Total moves imported: " . Move::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\Item;
use Illuminate\Console\Command;

class ImportItems extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-items
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Number of records to fetch per page}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon items from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->shouldRunParallel()) {
            return $this->runInParallel('/item', 'pokemon:import-items', [
                '--delay' => $this->option('delay'),
            ]);
        }

        $this->info('🎒 Importing Items...');

        try {
            $offset = $this->getStartOffset();
            $limit = (int) $this->option('limit');
            $maxItems = $this->getMaxItems();
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/item?limit={$limit}&offset={$offset}");
                $items = $response['results'] ?? [];

                if (empty($items) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $bar = $this->output->createProgressBar(count($items));
                $bar->start();

                foreach ($items as $itemData) {
                    if ($maxItems && $itemsProcessed >= $maxItems) break;

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

                        $this->recordSuccess();
                        $bar->advance();
                        $this->applyDelay();
                    } catch (\Exception $e) {
                        $this->recordError();
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($items) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->showStats('Items', Item::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

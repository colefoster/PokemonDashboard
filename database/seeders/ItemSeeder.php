<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Services\PokeApiService;
use App\Services\SeederProgressService;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
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
        $this->command->info('🎒 Importing Items...');

        try {
            $offset = 0;
            $limit = 100;

            // Get total count first
            $initialResponse = $this->api->fetch("/item?limit=1&offset=0");
            $totalCount = $initialResponse['count'] ?? 0;

            $this->progress->start('items', $totalCount);

            do {
                $response = $this->api->fetch("/item?limit={$limit}&offset={$offset}");
                $items = $response['results'] ?? [];

                if (empty($items)) {
                    break;
                }

                $bar = $this->command->getOutput()->createProgressBar(count($items));
                $bar->start();

                foreach ($items as $itemData) {
                    try {
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

                        $this->progress->advance("Importing item: {$itemDetails['name']}");
                        $this->progress->success();

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing item: " . $e->getMessage());
                        $this->progress->error($e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($items));

            $this->command->info("Items imported: " . Item::count());
            $this->progress->complete('items');
        } catch (\Exception $e) {
            $this->command->error('❌ Item import failed: ' . $e->getMessage());
            $this->progress->error($e->getMessage());
        }
    }
}

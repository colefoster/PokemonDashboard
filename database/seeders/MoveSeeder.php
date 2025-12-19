<?php

namespace Database\Seeders;

use App\Models\Move;
use App\Models\Type;
use App\Services\PokeApiService;
use App\Services\SeederProgressService;
use Illuminate\Database\Seeder;

class MoveSeeder extends Seeder
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
        $this->command->info('🥊 Importing Moves...');

        try {
            $offset = 0;
            $limit = 100;

            // Get total count first
            $initialResponse = $this->api->fetch("/move?limit=1&offset=0");
            $totalCount = $initialResponse['count'] ?? 0;

            $this->progress->start('moves', $totalCount);

            do {
                $response = $this->api->fetch("/move?limit={$limit}&offset={$offset}");
                $moves = $response['results'] ?? [];

                if (empty($moves)) {
                    break;
                }

                $bar = $this->command->getOutput()->createProgressBar(count($moves));
                $bar->start();

                foreach ($moves as $moveData) {
                    try {
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

                        $this->progress->advance("Importing move: {$moveDetails['name']}");
                        $this->progress->success();

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing move: " . $e->getMessage());
                        $this->progress->error($e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($moves));

            $this->command->info("Moves imported: " . Move::count());
            $this->progress->complete('moves');
        } catch (\Exception $e) {
            $this->command->error('❌ Move import failed: ' . $e->getMessage());
            $this->progress->error($e->getMessage());
        }
    }
}

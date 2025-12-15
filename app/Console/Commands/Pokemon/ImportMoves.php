<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\Move;
use App\Models\Type;
use Illuminate\Console\Command;

class ImportMoves extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-moves
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Number of records to fetch per page}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon moves from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->shouldRunParallel()) {
            return $this->runInParallel('/move', 'pokemon:import-moves', [
                '--delay' => $this->option('delay'),
            ]);
        }

        $this->info('🥊 Importing Moves...');

        try {
            $offset = $this->getStartOffset();
            $limit = (int) $this->option('limit');
            $maxItems = $this->getMaxItems();
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/move?limit={$limit}&offset={$offset}");
                $moves = $response['results'] ?? [];

                if (empty($moves) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $bar = $this->output->createProgressBar(count($moves));
                $bar->start();

                foreach ($moves as $moveData) {
                    if ($maxItems && $itemsProcessed >= $maxItems) break;

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

            } while (!empty($moves) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->showStats('Moves', Move::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

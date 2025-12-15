<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\Ability;
use Illuminate\Console\Command;

class ImportAbilities extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-abilities
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Number of records to fetch per page}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon abilities from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->shouldRunParallel()) {
            return $this->runInParallel('/ability', 'pokemon:import-abilities', [
                '--delay' => $this->option('delay'),
            ]);
        }

        $this->info('⚡ Importing Abilities...');

        try {
            $offset = $this->getStartOffset();
            $limit = (int) $this->option('limit');
            $maxItems = $this->getMaxItems();
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/ability?limit={$limit}&offset={$offset}");
                $abilities = $response['results'] ?? [];

                if (empty($abilities) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $bar = $this->output->createProgressBar(count($abilities));
                $bar->start();

                foreach ($abilities as $abilityData) {
                    if ($maxItems && $itemsProcessed >= $maxItems) break;

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

            } while (!empty($abilities) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->showStats('Abilities', Ability::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

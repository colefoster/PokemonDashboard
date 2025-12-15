<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\Type;
use Illuminate\Console\Command;

class ImportTypes extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-types
                            {--delay=100 : Delay between requests in milliseconds}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon types from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->shouldRunParallel()) {
            return $this->runInParallel('/type', 'pokemon:import-types', [
                '--delay' => $this->option('delay'),
            ]);
        }

        $this->info('📋 Importing Types...');

        try {
            $offset = $this->getStartOffset();
            $response = $this->api->fetch('/type?limit=100&offset=' . $offset);
            $types = $response['results'] ?? [];
            $maxItems = $this->getMaxItems();

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

                    $this->recordSuccess();
                    $bar->advance();
                    $this->applyDelay();
                } catch (\Exception $e) {
                    $this->recordError();
                }
            }

            $bar->finish();
            $this->showStats('Types', Type::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

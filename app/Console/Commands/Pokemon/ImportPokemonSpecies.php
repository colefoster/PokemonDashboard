<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\PokemonSpecies;
use Illuminate\Console\Command;

class ImportPokemonSpecies extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-species
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Number of records to fetch per page}
                            {--max= : Maximum number of species to import}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon species from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->shouldRunParallel()) {
            $maxSpecies = $this->option('max') ? (int) $this->option('max') : null;
            return $this->runInParallel('/pokemon-species', 'pokemon:import-species', [
                '--delay' => $this->option('delay'),
                '--max' => $maxSpecies,
            ]);
        }

        $this->info('🧬 Importing Pokemon Species...');

        try {
            $offset = $this->getStartOffset();
            $limit = (int) $this->option('limit');
            $maxSpecies = $this->option('max') ? (int) $this->option('max') : null;
            $maxItems = $this->getMaxItems();
            $totalImported = 0;

            do {
                $response = $this->api->fetch("/pokemon-species?limit={$limit}&offset={$offset}");
                $speciesList = $response['results'] ?? [];

                if (empty($speciesList) || ($maxSpecies && $totalImported >= $maxSpecies) || ($maxItems && $totalImported >= $maxItems)) {
                    break;
                }

                $bar = $this->output->createProgressBar(count($speciesList));
                $bar->start();

                foreach ($speciesList as $speciesData) {
                    if (($maxSpecies && $totalImported >= $maxSpecies) || ($maxItems && $totalImported >= $maxItems)) break;

                    try {
                        $speciesId = $this->api->extractIdFromUrl($speciesData['url']);
                        $speciesDetails = $this->api->fetch("/pokemon-species/{$speciesId}");

                        PokemonSpecies::updateOrCreate(
                            ['api_id' => $speciesDetails['id']],
                            [
                                'name' => $speciesDetails['name'],
                                'base_happiness' => $speciesDetails['base_happiness'],
                                'capture_rate' => $speciesDetails['capture_rate'],
                                'color' => $speciesDetails['color']['name'] ?? null,
                                'gender_rate' => $speciesDetails['gender_rate'],
                                'hatch_counter' => $speciesDetails['hatch_counter'],
                                'is_baby' => $speciesDetails['is_baby'] ?? false,
                                'is_legendary' => $speciesDetails['is_legendary'] ?? false,
                                'is_mythical' => $speciesDetails['is_mythical'] ?? false,
                                'habitat' => $speciesDetails['habitat']['name'] ?? null,
                                'shape' => $speciesDetails['shape']['name'] ?? null,
                                'generation' => $speciesDetails['generation']['name'] ?? null,
                            ]
                        );

                        $this->recordSuccess();
                        $totalImported++;
                        $bar->advance();
                        $this->applyDelay();
                    } catch (\Exception $e) {
                        $this->recordError();
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($speciesList) && (!$maxSpecies || $totalImported < $maxSpecies) && (!$maxItems || $totalImported < $maxItems));

            $this->showStats('Pokemon Species', PokemonSpecies::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

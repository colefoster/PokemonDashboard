<?php

namespace App\Console\Commands;

use App\Services\PokemonImportProgressService;
use Illuminate\Console\Command;

/**
 * Import Pokemon data from PokeAPI
 *
 * Usage:
 *   pz --max=50
 *   php artisan pokemon:import --delay=200 --max=151
 */
class ImportPokemon extends Command
{
    protected $signature = 'pokemon:import
                            {--limit=50 : Number of records to fetch per page}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--max= : Maximum number of pokemon to import (optional)}
                            {--threads=1 : Number of parallel workers for each import}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import all Pokemon data from PokeAPI (orchestrator command)';

    public function handle(): int
    {
        set_time_limit(0);

        $delay = (int) $this->option('delay');
        $limit = (int) $this->option('limit');
        $maxPokemon = $this->option('max') ? (int) $this->option('max') : null;
        $threads = (int) $this->option('threads');

        $progressService = app(PokemonImportProgressService::class);
        $importId = $this->option('import-id') ?? $progressService->startImport();

        if ($this->option('import-id')) {
            $progressService->setImportId($importId);
        }

        $this->info('🚀 Starting Pokemon import from PokeAPI...');
        $this->info("Import ID: $importId");
        $this->info("Delay between requests: {$delay}ms");
        if ($maxPokemon) {
            $this->info("Maximum Pokemon to import: $maxPokemon");
        }
        if ($threads > 1) {
            $this->info("Parallel workers: $threads");
        }
        $this->newLine();

        $commands = [
            ['pokemon:import-types', 'Types'],
            ['pokemon:import-abilities', 'Abilities'],
            ['pokemon:import-moves', 'Moves'],
            ['pokemon:import-items', 'Items'],
            ['pokemon:import-species', 'Pokemon Species'],
            ['pokemon:import-evolution-chains', 'Evolution Chains'],
            ['pokemon:import-data', 'Pokemon Data'],
        ];

        try {
            foreach ($commands as $index => [$command, $label]) {
                $progressService->startStep($label, $index);

                $options = [
                    '--delay' => $delay,
                    '--threads' => $threads,
                    '--import-id' => $importId,
                ];

                if (in_array($command, ['pokemon:import-species', 'pokemon:import-data']) && $maxPokemon) {
                    $options['--max'] = $maxPokemon;
                }

                if ($command === 'pokemon:import-data') {
                    $options['--limit'] = $limit;
                }

                // Add limit for all paginated commands
                if ($command !== 'pokemon:import-types') {
                    $options['--limit'] = $limit;
                }

                $result = $this->call($command, $options);

                if ($result !== self::SUCCESS) {
                    $progressService->failImport("Failed to import $label");
                    $this->error("❌ Failed to import $label");
                    return self::FAILURE;
                }

                $progressService->completeStep();
                $this->newLine();
            }

            $progressService->completeImport();
            $this->info('✅ All imports completed successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $progressService->failImport($e->getMessage());
            $this->error('❌ Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

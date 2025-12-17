<?php

namespace App\Console\Commands\Seed;

use App\Models\Ability;
use App\Models\Item;
use App\Models\Move;
use App\Models\Pokemon;
use App\Models\PokemonGameIndex;
use App\Models\PokemonSpecies;
use App\Models\PokemonStat;
use App\Models\Type;
use App\Services\PokeApiService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SeedPokemon extends Command
{
    protected $signature = 'seed:pokemon
                            {--threads=1 : Number of parallel workers}
                            {--worker-id= : Worker ID (internal use)}
                            {--offset=0 : Starting offset}
                            {--max-items= : Maximum items for this worker}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=50 : Items per page}';

    protected $description = 'Seed pokemon table from PokeAPI with parallel processing support';

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
        $this->info($prefix . '🎮 Importing Pokemon...');

        try {
            $offset = (int) $this->option('offset');
            $limit = (int) $this->option('limit');
            $maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
            $itemsProcessed = 0;

            do {
                $response = $this->api->fetch("/pokemon?limit={$limit}&offset={$offset}");
                $pokemonList = $response['results'] ?? [];

                if (empty($pokemonList) || ($maxItems && $itemsProcessed >= $maxItems)) break;

                $remaining = $maxItems ? min(count($pokemonList), $maxItems - $itemsProcessed) : count($pokemonList);
                $bar = $this->output->createProgressBar($remaining);
                $bar->start();

                foreach (array_slice($pokemonList, 0, $remaining) as $pokemonData) {
                    try {
                        $itemsProcessed++;
                        $pokemonId = $this->api->extractIdFromUrl($pokemonData['url']);
                        $pokemonDetails = $this->api->fetch("/pokemon/{$pokemonId}");

                        $pokemon = $this->createPokemon($pokemonDetails);
                        $this->importStats($pokemon, $pokemonDetails);
                        $this->syncTypes($pokemon, $pokemonDetails);
                        $this->syncAbilities($pokemon, $pokemonDetails);
                        $this->syncMoves($pokemon, $pokemonDetails);
                        $this->syncItems($pokemon, $pokemonDetails);
                        $this->importGameIndices($pokemon, $pokemonDetails);

                        $bar->advance();
                        usleep($this->delay * 1000);
                    } catch (\Exception $e) {
                        // Silent error handling
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($pokemonList) && (!$maxItems || $itemsProcessed < $maxItems));

            $this->info($prefix . "Pokemon imported: " . Pokemon::count());
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Pokemon import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function createPokemon(array $pokemonDetails): Pokemon
    {
        $speciesId = null;
        if (isset($pokemonDetails['species']['name'])) {
            $species = PokemonSpecies::where('name', $pokemonDetails['species']['name'])->first();
            $speciesId = $species?->id;
        }

        return Pokemon::updateOrCreate(
            ['api_id' => $pokemonDetails['id']],
            [
                'name' => $pokemonDetails['name'],
                'height' => $pokemonDetails['height'],
                'weight' => $pokemonDetails['weight'],
                'base_experience' => $pokemonDetails['base_experience'],
                'is_default' => $pokemonDetails['is_default'] ?? true,
                'species_id' => $speciesId,
                'sprite_front_default' => $pokemonDetails['sprites']['front_default'] ?? null,
                'sprite_front_shiny' => $pokemonDetails['sprites']['front_shiny'] ?? null,
                'sprite_back_default' => $pokemonDetails['sprites']['back_default'] ?? null,
                'sprite_back_shiny' => $pokemonDetails['sprites']['back_shiny'] ?? null,
                'cry_latest' => $pokemonDetails['cries']['latest'] ?? null,
                'cry_legacy' => $pokemonDetails['cries']['legacy'] ?? null,
            ]
        );
    }

    private function importStats(Pokemon $pokemon, array $pokemonDetails): void
    {
        foreach ($pokemonDetails['stats'] ?? [] as $statData) {
            PokemonStat::updateOrCreate(
                [
                    'pokemon_id' => $pokemon->id,
                    'stat_name' => $statData['stat']['name'],
                ],
                [
                    'base_stat' => $statData['base_stat'],
                    'effort' => $statData['effort'],
                ]
            );
        }
    }

    private function syncTypes(Pokemon $pokemon, array $pokemonDetails): void
    {
        $typeIds = [];
        foreach ($pokemonDetails['types'] ?? [] as $typeData) {
            $type = Type::where('name', $typeData['type']['name'])->first();
            if ($type) {
                $typeIds[$type->id] = ['slot' => $typeData['slot']];
            }
        }
        $pokemon->types()->sync($typeIds);
    }

    private function syncAbilities(Pokemon $pokemon, array $pokemonDetails): void
    {
        $abilityIds = [];
        foreach ($pokemonDetails['abilities'] ?? [] as $abilityData) {
            $ability = Ability::where('name', $abilityData['ability']['name'])->first();
            if ($ability) {
                $abilityIds[$ability->id] = [
                    'is_hidden' => $abilityData['is_hidden'],
                    'slot' => $abilityData['slot'],
                ];
            }
        }
        $pokemon->abilities()->sync($abilityIds);
    }

    private function syncMoves(Pokemon $pokemon, array $pokemonDetails): void
    {
        $moveIds = [];
        foreach ($pokemonDetails['moves'] ?? [] as $moveData) {
            $move = Move::where('name', $moveData['move']['name'])->first();
            if ($move && !isset($moveIds[$move->id])) {
                $versionGroupDetails = $moveData['version_group_details'][0] ?? null;
                $moveIds[$move->id] = [
                    'learn_method' => $versionGroupDetails['move_learn_method']['name'] ?? null,
                    'level_learned_at' => $versionGroupDetails['level_learned_at'] ?? null,
                ];
            }
        }
        $pokemon->moves()->sync($moveIds);
    }

    private function syncItems(Pokemon $pokemon, array $pokemonDetails): void
    {
        $itemIds = [];
        foreach ($pokemonDetails['held_items'] ?? [] as $heldItemData) {
            $item = Item::where('name', $heldItemData['item']['name'])->first();
            if ($item) {
                $versionDetail = $heldItemData['version_details'][0] ?? null;
                if ($versionDetail) {
                    $itemIds[$item->id] = [
                        'rarity' => $versionDetail['rarity'] ?? null,
                        'version' => $versionDetail['version']['name'] ?? null,
                    ];
                }
            }
        }
        $pokemon->items()->sync($itemIds);
    }

    private function importGameIndices(Pokemon $pokemon, array $pokemonDetails): void
    {
        PokemonGameIndex::where('pokemon_id', $pokemon->id)->delete();
        foreach ($pokemonDetails['game_indices'] ?? [] as $gameIndexData) {
            PokemonGameIndex::create([
                'pokemon_id' => $pokemon->id,
                'game_index' => $gameIndexData['game_index'],
                'version' => $gameIndexData['version']['name'] ?? null,
            ]);
        }
    }

    protected function runInParallel(): int
    {
        $threads = (int) $this->option('threads');
        $this->info("🚀 Starting parallel processing with {$threads} threads...");

        $response = $this->api->fetch('/pokemon?limit=1');
        $totalItems = $response['count'] ?? 0;

        if ($totalItems === 0) {
            $this->warn('No pokemon found to import.');
            return self::SUCCESS;
        }

        $itemsPerThread = (int) ceil($totalItems / $threads);
        $processes = [];

        for ($i = 0; $i < $threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) break;

            $command = [
                PHP_BINARY, 'artisan', 'seed:pokemon',
                '--threads=1', '--worker-id=' . $i,
                '--offset=' . $offset, '--max-items=' . $maxItems,
                '--delay=' . $this->option('delay'),
                '--limit=' . $this->option('limit'),
            ];

            $process = new Process($command, base_path());
            $process->setTimeout(7200); // 2 hours for pokemon (lots of relationships)
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
            $this->info("Total pokemon imported: " . Pokemon::count());
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }
}

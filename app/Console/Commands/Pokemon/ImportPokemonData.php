<?php

namespace App\Console\Commands\Pokemon;

use App\Console\Commands\Traits\ImportsPokemonData;
use App\Models\Ability;
use App\Models\Item;
use App\Models\Move;
use App\Models\Pokemon;
use App\Models\PokemonGameIndex;
use App\Models\PokemonSpecies;
use App\Models\PokemonStat;
use App\Models\Type;
use Illuminate\Console\Command;

class ImportPokemonData extends Command
{
    use ImportsPokemonData;

    protected $signature = 'pokemon:import-data
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=50 : Number of records to fetch per page}
                            {--max= : Maximum number of pokemon to import}
                            {--threads=1 : Number of parallel workers}
                            {--offset=0 : Starting offset (used by workers)}
                            {--max-items= : Maximum items for this worker}
                            {--worker-id= : Worker ID (internal use)}
                            {--import-id= : Import ID for tracking progress (internal use)}';

    protected $description = 'Import Pokemon data (main entities with relationships) from PokeAPI';

    public function handle(): int
    {
        $this->initializeImporter((int) $this->option('delay'));

        if ($this->option('import-id')) {
            $progressService = app(\App\Services\PokemonImportProgressService::class);
            $progressService->setImportId($this->option('import-id'));
            $this->setProgressService($progressService);
        }

        if ($this->shouldRunParallel()) {
            $maxPokemon = $this->option('max') ? (int) $this->option('max') : null;
            return $this->runInParallel('/pokemon', 'pokemon:import-data', [
                '--delay' => $this->option('delay'),
                '--max' => $maxPokemon,
            ]);
        }

        $this->info('🎮 Importing Pokemon...');

        try {
            $offset = $this->getStartOffset();
            $limit = (int) $this->option('limit');
            $maxPokemon = $this->option('max') ? (int) $this->option('max') : null;
            $maxItems = $this->getMaxItems();
            $totalImported = 0;

            do {
                $response = $this->api->fetch("/pokemon?limit={$limit}&offset={$offset}");
                $pokemonList = $response['results'] ?? [];

                if (empty($pokemonList) || ($maxPokemon && $totalImported >= $maxPokemon) || ($maxItems && $totalImported >= $maxItems)) {
                    break;
                }

                $bar = $this->output->createProgressBar(count($pokemonList));
                $bar->start();

                foreach ($pokemonList as $pokemonData) {
                    if (($maxPokemon && $totalImported >= $maxPokemon) || ($maxItems && $totalImported >= $maxItems)) break;

                    try {
                        $pokemonId = $this->api->extractIdFromUrl($pokemonData['url']);
                        $pokemonDetails = $this->api->fetch("/pokemon/{$pokemonId}");

                        $pokemon = $this->createPokemon($pokemonDetails);
                        $this->importStats($pokemon, $pokemonDetails);
                        $this->syncTypes($pokemon, $pokemonDetails);
                        $this->syncAbilities($pokemon, $pokemonDetails);
                        $this->syncMoves($pokemon, $pokemonDetails);
                        $this->syncItems($pokemon, $pokemonDetails);
                        $this->importGameIndices($pokemon, $pokemonDetails);

                        $this->recordSuccess();
                        $totalImported++;
                        $this->updateProgress($totalImported, $maxPokemon ?? $maxItems);
                        $bar->advance();
                        $this->applyDelay();

                    } catch (\Exception $e) {
                        $this->recordError();
                        $this->warn("\nError importing pokemon: " . $e->getMessage());
                    }
                }

                $bar->finish();
                $this->newLine();
                $offset += $limit;

            } while (!empty($pokemonList) && (!$maxPokemon || $totalImported < $maxPokemon) && (!$maxItems || $totalImported < $maxItems));

            $this->showStats('Pokemon', Pokemon::count());

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
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
}

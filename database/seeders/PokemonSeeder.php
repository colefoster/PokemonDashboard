<?php

namespace Database\Seeders;

use App\Models\Ability;
use App\Models\Item;
use App\Models\Move;
use App\Models\Pokemon;
use App\Models\PokemonGameIndex;
use App\Models\PokemonSpecies;
use App\Models\PokemonStat;
use App\Models\Type;
use App\Services\PokeApiService;
use App\Services\SeederProgressService;
use Illuminate\Database\Seeder;

class PokemonSeeder extends Seeder
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
        $this->command->info('🎮 Importing Pokemon...');

        try {
            $offset = 0;
            $limit = 50;

            // Get total count first
            $initialResponse = $this->api->fetch("/pokemon?limit=1&offset=0");
            $totalCount = $initialResponse['count'] ?? 0;

            $this->progress->start('pokemon', $totalCount);

            do {
                $response = $this->api->fetch("/pokemon?limit={$limit}&offset={$offset}");
                $pokemonList = $response['results'] ?? [];

                if (empty($pokemonList)) {
                    break;
                }

                $bar = $this->command->getOutput()->createProgressBar(count($pokemonList));
                $bar->start();

                foreach ($pokemonList as $pokemonData) {
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

                        $this->progress->advance("Importing pokemon: {$pokemonDetails['name']}");
                        $this->progress->success();

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing pokemon: " . $e->getMessage());
                        $this->progress->error($e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($pokemonList));

            $this->command->info("Pokemon imported: " . Pokemon::count());
            $this->progress->complete('pokemon');
        } catch (\Exception $e) {
            $this->command->error('❌ Pokemon import failed: ' . $e->getMessage());
            $this->progress->error($e->getMessage());
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


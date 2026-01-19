<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use App\Models\Move;
use App\Models\Item;
use App\Models\Type;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    /**
     * Base URL for Smogon sets data
     */
    private const SMOGON_SETS_URL = 'https://pkmn.github.io/smogon/data/sets';

    /**
     * Base URL for Smogon usage stats data
     */
    private const SMOGON_STATS_URL = 'https://pkmn.github.io/smogon/data/stats';

    /**
     * Get paginated list of Pokemon with their types and stats
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page');
        $search = $request->input('search');

        $query = Pokemon::with(['types', 'stats', 'species'])
            ->where('is_default', true);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if (!$perPage) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Get a single Pokemon with full details
     */
    public function show(int $apiId): JsonResponse
    {
        $pokemon = $this->getPokemonQuery()
            ->where('api_id', $apiId)
            ->firstOrFail();

        return response()->json($pokemon);
    }

    /**
     * Search Pokemon by name
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        $pokemon = Pokemon::with(['types', 'stats'])
            ->where('is_default', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('api_id', $query);
            })
            ->limit(20)
            ->get();

        return response()->json($pokemon);
    }

    // =========================================================================
    // Format/Sets Endpoints
    // =========================================================================

    /**
     * Get all sets data for a format (raw Smogon data)
     * GET /api/formats/{format}/sets
     */
    public function getSets(string $format): JsonResponse
    {
        $setsData = $this->fetchSetsData($format);

        return response()->json($setsData);
    }

    /**
     * Search sets by Pokemon name within a format
     * GET /api/formats/{format}/sets/search?q=name
     */
    public function searchSets(Request $request, string $format): JsonResponse
    {
        $query = strtolower($request->input('q', ''));
        $setsData = $this->fetchSetsData($format);

        if (!$query) {
            return response()->json($setsData);
        }

        $filtered = collect($setsData)->filter(function ($sets, $pokemonName) use ($query) {
            return str_contains(strtolower($pokemonName), $query);
        });

        return response()->json($filtered);
    }

    /**
     * Get all Pokemon names in a format
     * GET /api/formats/{format}/names
     */
    public function getNames(string $format): JsonResponse
    {
        $setsData = $this->fetchSetsData($format);
        $names = array_keys($setsData);

        return response()->json($names);
    }

    /**
     * Get Pokemon database data for all Pokemon in a format
     * GET /api/formats/{format}/pokemon
     */
    public function getPokemonInFormat(string $format): JsonResponse
    {
        $setsData = $this->fetchSetsData($format);
        $pokemonNames = array_keys($setsData);

        $pokemon = $this->findPokemonByNames($pokemonNames);

        return response()->json($pokemon->values());
    }

    /**
     * Search Pokemon data by name within a format
     * GET /api/formats/{format}/pokemon/search?q=name
     */
    public function searchPokemonInFormat(Request $request, string $format): JsonResponse
    {
        $query = strtolower($request->input('q', ''));
        $setsData = $this->fetchSetsData($format);

        // Filter to names matching the query
        $matchingNames = collect(array_keys($setsData))
            ->filter(fn ($name) => str_contains(strtolower($name), $query))
            ->values()
            ->toArray();

        if (empty($matchingNames)) {
            return response()->json([]);
        }

        $pokemon = $this->findPokemonByNames($matchingNames);

        return response()->json($pokemon->values());
    }

    /**
     * Get combined sets + Pokemon data for a format
     * GET /api/formats/{format}/combined
     */
    public function getCombined(string $format): JsonResponse
    {
        $setsData = $this->fetchSetsData($format);

        $combined = $this->combineSetsWithPokemon($setsData);

        return response()->json($combined->values());
    }

    /**
     * Search combined sets + Pokemon data by name
     * GET /api/formats/{format}/combined/search?q=name
     */
    public function searchCombined(Request $request, string $format): JsonResponse
    {
        $query = strtolower($request->input('q', ''));
        $setsData = $this->fetchSetsData($format);

        // Filter sets to matching names first
        $filteredSets = collect($setsData)->filter(function ($sets, $pokemonName) use ($query) {
            return empty($query) || str_contains(strtolower($pokemonName), $query);
        })->toArray();

        $combined = $this->combineSetsWithPokemon($filteredSets);

        return response()->json($combined->values());
    }

    // =========================================================================
    // Usage Stats Endpoints
    // =========================================================================

    /**
     * Get all usage stats for a format
     * GET /api/formats/{format}/stats
     */
    public function getStats(string $format): JsonResponse
    {
        $statsData = $this->fetchStatsData($format);

        return response()->json($statsData);
    }

    /**
     * Get usage stats for a specific Pokemon in a format
     * GET /api/formats/{format}/stats/{pokemon}
     */
    public function getStatsForPokemon(string $format, string $pokemon): JsonResponse
    {
        $statsData = $this->fetchStatsData($format);

        if (empty($statsData) || !isset($statsData['pokemon'])) {
            return response()->json(['error' => 'Format not found'], 404);
        }

        // Try exact match first, then case-insensitive match
        $pokemonStats = $statsData['pokemon'][$pokemon] ?? null;

        if (!$pokemonStats) {
            // Try case-insensitive search
            $searchName = strtolower($pokemon);
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === $searchName) {
                    $pokemonStats = $stats;
                    $pokemon = $name;
                    break;
                }
            }
        }

        if (!$pokemonStats) {
            return response()->json(['error' => 'Pokemon not found in this format'], 404);
        }

        return response()->json([
            'name' => $pokemon,
            'battles' => $statsData['battles'] ?? null,
            'stats' => $pokemonStats,
        ]);
    }

    /**
     * Search usage stats by Pokemon name within a format
     * GET /api/formats/{format}/stats/search?q=name
     */
    public function searchStats(Request $request, string $format): JsonResponse
    {
        $query = strtolower($request->input('q', ''));
        $statsData = $this->fetchStatsData($format);

        if (empty($statsData) || !isset($statsData['pokemon'])) {
            return response()->json([]);
        }

        if (!$query) {
            return response()->json($statsData);
        }

        $filtered = collect($statsData['pokemon'])->filter(function ($stats, $pokemonName) use ($query) {
            return str_contains(strtolower($pokemonName), $query);
        });

        return response()->json([
            'battles' => $statsData['battles'] ?? null,
            'pokemon' => $filtered,
        ]);
    }

    /**
     * Get combined stats + Pokemon database data for a format
     * GET /api/formats/{format}/stats/combined
     */
    public function getStatsCombined(string $format): JsonResponse
    {
        $statsData = $this->fetchStatsData($format);

        if (empty($statsData) || !isset($statsData['pokemon'])) {
            return response()->json([]);
        }

        $combined = $this->combineStatsWithPokemon($statsData['pokemon']);

        return response()->json([
            'battles' => $statsData['battles'] ?? null,
            'pokemon' => $combined->values(),
        ]);
    }

    /**
     * Get usage stats ranked by usage (sorted)
     * GET /api/formats/{format}/stats/ranked?limit=50
     */
    public function getStatsRanked(Request $request, string $format): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $statsData = $this->fetchStatsData($format);

        if (empty($statsData) || !isset($statsData['pokemon'])) {
            return response()->json([]);
        }

        $ranked = collect($statsData['pokemon'])
            ->map(fn ($stats, $name) => array_merge(['name' => $name], $stats))
            ->sortByDesc(fn ($pokemon) => $pokemon['usage']['weighted'] ?? 0)
            ->take($limit)
            ->values();

        return response()->json([
            'battles' => $statsData['battles'] ?? null,
            'pokemon' => $ranked,
        ]);
    }

    /**
     * Get Pokemon names with usage percentages, optionally sorted by usage
     * GET /api/formats/{format}/names/usage?sort=usage
     */
    public function getNamesWithUsage(Request $request, string $format): JsonResponse
    {
        $sortBy = $request->input('sort', 'name'); // 'name' or 'usage'
        $statsData = $this->fetchStatsData($format);

        if (empty($statsData) || !isset($statsData['pokemon'])) {
            // Fallback to sets names without usage data
            $setsData = $this->fetchSetsData($format);
            $names = collect(array_keys($setsData))->map(fn ($name) => [
                'name' => $name,
                'usage' => null,
            ]);

            return response()->json($names->values());
        }

        $pokemonWithUsage = collect($statsData['pokemon'])
            ->map(fn ($stats, $name) => [
                'name' => $name,
                'usage' => $stats['usage']['weighted'] ?? $stats['usage']['raw'] ?? 0,
            ]);

        // Sort by usage or name
        if ($sortBy === 'usage') {
            $pokemonWithUsage = $pokemonWithUsage->sortByDesc('usage');
        } else {
            $pokemonWithUsage = $pokemonWithUsage->sortBy('name');
        }

        return response()->json($pokemonWithUsage->values());
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Fetch usage stats data from Smogon API (cached for 1 hour)
     */
    private function fetchStatsData(string $format): array
    {
        $format = $this->normalizeFormat($format);
        $cacheKey = "smogon_stats_{$format}";

        return Cache::remember($cacheKey, 3600, function () use ($format) {
            $response = Http::get(self::SMOGON_STATS_URL . "/{$format}.json");

            if (!$response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        });
    }

    /**
     * Fetch sets data from Smogon API (cached for 1 hour)
     */
    private function fetchSetsData(string $format): array
    {
        $format = $this->normalizeFormat($format);
        $cacheKey = "smogon_sets_{$format}";

        return Cache::remember($cacheKey, 3600, function () use ($format) {
            $response = Http::get(self::SMOGON_SETS_URL . "/{$format}.json");

            if (!$response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        });
    }

    /**
     * Normalize format string (e.g., "9" -> "gen9", "Gen9OU" -> "gen9ou")
     */
    private function normalizeFormat(string $format): string
    {
        $format = strtolower($format);

        // If it's just a number, prepend "gen"
        if (is_numeric($format)) {
            return "gen{$format}";
        }

        // If it doesn't start with "gen", prepend it
        if (!str_starts_with($format, 'gen')) {
            return "gen{$format}";
        }

        return $format;
    }

    /**
     * Normalize a Pokemon name for database lookup
     * Smogon: "Tapu Koko", "Urshifu-Rapid-Strike"
     * Database: "tapu-koko", "urshifu-rapid-strike"
     */
    private function normalizePokemonName(string $name): string
    {
        return strtolower(str_replace(' ', '-', $name));
    }

    /**
     * Convert a Pokemon name to the format used by Pokemon Showdown sprites
     */
    private function toShowdownSpriteName(string $name): string
    {
        // Convert to lowercase
        $name = strtolower(trim($name));

        // Pokemon with hyphens in their actual names (not form separators)
        $hyphenatedPokemon = [
            'ho-oh', 'porygon-z', 'jangmo-o', 'hakamo-o', 'kommo-o',
            'tapu-koko', 'tapu-lele', 'tapu-bulu', 'tapu-fini',
            'chi-yu', 'chien-pao', 'ting-lu', 'wo-chien',
        ];

        // Check if this is a Pokemon with a hyphen in its actual name
        $normalized = preg_replace("/[^a-z0-9-]/", '', str_replace(' ', '-', $name));

        foreach ($hyphenatedPokemon as $hyphenated) {
            if (str_starts_with($normalized, $hyphenated)) {
                // Keep the hyphen for this Pokemon
                return $normalized;
            }
        }

        // For other Pokemon, remove spaces and hyphens (form indicators)
        // e.g., "Great Tusk" -> "greattusk", "Iron Valiant" -> "ironvaliant"
        return preg_replace("/[^a-z0-9]/", '', $name);
    }

    /**
     * Get a fallback sprite URL from Pokemon Showdown's CDN
     */
    private function getShowdownSpriteUrl(string $name): string
    {
        $spriteName = $this->toShowdownSpriteName($name);
        return "https://play.pokemonshowdown.com/sprites/gen5/{$spriteName}.png";
    }

    /**
     * Get base Pokemon query with common relations
     *
     * @param bool $defaultOnly If true, only return default forms (excludes regional variants)
     */
    private function getPokemonQuery(bool $defaultOnly = true)
    {
        $query = Pokemon::with([
            'types',
            'abilities',
            'moves',
            'stats',
            'species.evolutionChain.evolutions'
        ]);

        if ($defaultOnly) {
            $query->where('is_default', true);
        }

        return $query;
    }

    /**
     * Find Pokemon by an array of Smogon names
     */
    private function findPokemonByNames(array $names): Collection
    {
        $normalizedNames = collect($names)->map(fn ($name) => $this->normalizePokemonName($name));

        // Use defaultOnly: false to include regional forms (Alola, Galar, Hisui, Paldea)
        return $this->getPokemonQuery(defaultOnly: false)
            ->where(function ($query) use ($normalizedNames) {
                foreach ($normalizedNames as $name) {
                    // Case-insensitive comparison using LOWER()
                    $query->orWhereRaw('LOWER(name) = ?', [$name]);
                }
            })
            ->get()
            ->keyBy(fn ($pokemon) => strtolower($pokemon->name));
    }

    /**
     * Combine sets data with Pokemon database records
     */
    private function combineSetsWithPokemon(array $setsData): Collection
    {
        $names = array_keys($setsData);
        $pokemonByName = $this->findPokemonByNames($names);

        return collect($setsData)->map(function ($sets, $smogonName) use ($pokemonByName) {
            $normalizedName = $this->normalizePokemonName($smogonName);

            // Try exact match first, then partial match
            $pokemon = $pokemonByName->get($normalizedName)
                ?? $pokemonByName->first(fn ($p) => str_contains(strtolower($p->name), $normalizedName));

            return [
                'name' => $smogonName,
                'sets' => $sets,
                'pokemon' => $pokemon,
            ];
        })->filter(fn ($item) => $item['pokemon'] !== null);
    }

    /**
     * Combine usage stats data with Pokemon database records
     */
    private function combineStatsWithPokemon(array $pokemonStats): Collection
    {
        $names = array_keys($pokemonStats);
        $pokemonByName = $this->findPokemonByNames($names);

        return collect($pokemonStats)->map(function ($stats, $smogonName) use ($pokemonByName) {
            $normalizedName = $this->normalizePokemonName($smogonName);

            // Try exact match first, then partial match
            $pokemon = $pokemonByName->get($normalizedName)
                ?? $pokemonByName->first(fn ($p) => str_contains(strtolower($p->name), $normalizedName));

            return [
                'name' => $smogonName,
                'stats' => $stats,
                'pokemon' => $pokemon,
            ];
        })->filter(fn ($item) => $item['pokemon'] !== null);
    }

    // =========================================================================
    // Moves, Items, Types, and Natures Endpoints
    // =========================================================================

    /**
     * Search moves with type information
     * GET /api/moves/search?q=name&pokemon_id=123
     */
    public function searchMoves(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $pokemonId = $request->input('pokemon_id');
        $limit = $request->input('limit', 50);

        $movesQuery = Move::with('type');

        // If pokemon_id is provided, filter to moves that Pokemon can learn
        if ($pokemonId) {
            $movesQuery->whereHas('pokemon', function ($q) use ($pokemonId) {
                $q->where('pokemon.api_id', $pokemonId);
            });
        }

        // Search by name
        if ($query) {
            $searchTerm = strtolower(str_replace(' ', '-', $query));
            $movesQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $moves = $movesQuery
            ->limit($limit)
            ->get()
            ->map(function ($move) {
                return [
                    'id' => $move->id,
                    'api_id' => $move->api_id,
                    'name' => $move->name,
                    'type' => $move->type ? [
                        'id' => $move->type->id,
                        'name' => $move->type->name,
                    ] : null,
                    'power' => $move->power,
                    'accuracy' => $move->accuracy,
                    'pp' => $move->pp,
                    'priority' => $move->priority,
                    'damage_class' => $move->damage_class,
                    'effect' => $move->short_effect ?? $move->effect,
                ];
            });

        return response()->json($moves);
    }

    /**
     * Search items
     * GET /api/items/search?q=name&category=held-items
     */
    public function searchItems(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $category = $request->input('category');
        $limit = $request->input('limit', 50);

        $itemsQuery = Item::query();

        // Filter by category if provided
        if ($category) {
            $itemsQuery->whereRaw('LOWER(category) = ?', [strtolower($category)]);
        }

        // Search by name
        if ($query) {
            $searchTerm = strtolower(str_replace(' ', '-', $query));
            $itemsQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $items = $itemsQuery
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'api_id' => $item->api_id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'effect' => $item->short_effect ?? $item->effect,
                    'sprite' => $item->sprite,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get all types
     * GET /api/types
     */
    public function getTypes(): JsonResponse
    {
        $types = Type::all()->map(function ($type) {
            return [
                'id' => $type->id,
                'api_id' => $type->api_id,
                'name' => $type->name,
            ];
        });

        return response()->json($types);
    }

    /**
     * Get moves for a specific Pokemon with usage stats from Smogon
     * GET /api/formats/{format}/pokemon/{pokemon}/moves?q=search
     */
    public function getPokemonMovesWithUsage(Request $request, string $format, string $pokemon): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = $request->input('limit', 50);

        // Get Smogon stats for move usage
        $statsData = $this->fetchStatsData($format);
        $moveUsage = [];

        if (!empty($statsData['pokemon'])) {
            // Try to find the Pokemon in stats (case-insensitive)
            $pokemonName = null;
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === strtolower($pokemon) ||
                    strtolower(str_replace(' ', '-', $name)) === strtolower($pokemon)) {
                    $pokemonName = $name;
                    $moveUsage = $stats['moves'] ?? [];
                    break;
                }
            }
        }

        // Convert to normalized format (move name => usage percentage)
        $normalizedUsage = [];
        foreach ($moveUsage as $moveName => $usage) {
            $normalizedName = strtolower(str_replace(' ', '-', $moveName));
            $normalizedUsage[$normalizedName] = $usage;
        }

        // Fetch moves from database
        $pokemonModel = Pokemon::where('api_id', $pokemon)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($pokemon)])
            ->first();

        if (!$pokemonModel) {
            return response()->json([]);
        }

        $movesQuery = Move::with('type')
            ->whereHas('pokemon', function ($q) use ($pokemonModel) {
                $q->where('pokemon.id', $pokemonModel->id);
            });

        if ($query) {
            $searchTerm = strtolower(str_replace(' ', '-', $query));
            $movesQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $moves = $movesQuery->get()
            ->map(function ($move) use ($normalizedUsage) {
                $normalizedName = strtolower(str_replace(' ', '-', $move->getRawOriginal('name')));
                $usage = $normalizedUsage[$normalizedName] ?? null;

                return [
                    'id' => $move->id,
                    'api_id' => $move->api_id,
                    'name' => $move->name,
                    'type' => $move->type ? [
                        'id' => $move->type->id,
                        'name' => $move->type->name,
                    ] : null,
                    'power' => $move->power,
                    'accuracy' => $move->accuracy,
                    'pp' => $move->pp,
                    'priority' => $move->priority,
                    'damage_class' => $move->damage_class,
                    'effect' => $move->short_effect ?? $move->effect,
                    'usage' => $usage,
                ];
            })
            ->sortByDesc('usage')
            ->take($limit)
            ->values();

        return response()->json($moves);
    }

    /**
     * Get abilities for a specific Pokemon with usage stats from Smogon
     * GET /api/formats/{format}/pokemon/{pokemon}/abilities
     */
    public function getPokemonAbilitiesWithUsage(Request $request, string $format, string $pokemon): JsonResponse
    {
        // Get Smogon stats for ability usage
        $statsData = $this->fetchStatsData($format);
        $abilityUsage = [];

        if (!empty($statsData['pokemon'])) {
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === strtolower($pokemon) ||
                    strtolower(str_replace(' ', '-', $name)) === strtolower($pokemon)) {
                    $abilityUsage = $stats['abilities'] ?? [];
                    break;
                }
            }
        }

        // Normalize ability usage
        $normalizedUsage = [];
        foreach ($abilityUsage as $abilityName => $usage) {
            $normalizedName = strtolower(str_replace(' ', '-', $abilityName));
            $normalizedUsage[$normalizedName] = $usage;
        }

        // Get Pokemon's abilities from database
        $pokemonModel = Pokemon::with(['abilities'])
            ->where('api_id', $pokemon)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($pokemon)])
            ->first();

        if (!$pokemonModel) {
            return response()->json([]);
        }

        $abilities = $pokemonModel->abilities->map(function ($ability) use ($normalizedUsage) {
            $normalizedName = strtolower(str_replace(' ', '-', $ability->getRawOriginal('name')));
            $usage = $normalizedUsage[$normalizedName] ?? null;

            return [
                'id' => $ability->id,
                'api_id' => $ability->api_id,
                'name' => $ability->name,
                'short_effect' => $ability->short_effect,
                'is_hidden' => $ability->pivot->is_hidden ?? false,
                'usage' => $usage,
            ];
        })
        ->sortByDesc('usage')
        ->values();

        return response()->json($abilities);
    }

    /**
     * Get items with usage stats for a specific Pokemon from Smogon
     * GET /api/formats/{format}/pokemon/{pokemon}/items?q=search
     */
    public function getPokemonItemsWithUsage(Request $request, string $format, string $pokemon): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = $request->input('limit', 50);

        // Get Smogon stats for item usage
        $statsData = $this->fetchStatsData($format);
        $itemUsage = [];

        if (!empty($statsData['pokemon'])) {
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === strtolower($pokemon) ||
                    strtolower(str_replace(' ', '-', $name)) === strtolower($pokemon)) {
                    $itemUsage = $stats['items'] ?? [];
                    break;
                }
            }
        }

        // Normalize item usage
        $normalizedUsage = [];
        foreach ($itemUsage as $itemName => $usage) {
            $normalizedName = strtolower(str_replace(' ', '-', $itemName));
            $normalizedUsage[$normalizedName] = $usage;
        }

        // Search items from database
        $itemsQuery = Item::query();

        if ($query) {
            $searchTerm = strtolower(str_replace(' ', '-', $query));
            $itemsQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
        }

        $items = $itemsQuery->get()
            ->map(function ($item) use ($normalizedUsage) {
                $normalizedName = strtolower(str_replace(' ', '-', $item->getRawOriginal('name')));
                $usage = $normalizedUsage[$normalizedName] ?? null;

                return [
                    'id' => $item->id,
                    'api_id' => $item->api_id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'effect' => $item->short_effect ?? $item->effect,
                    'sprite' => $item->sprite,
                    'usage' => $usage,
                ];
            })
            ->sortByDesc('usage')
            ->take($limit)
            ->values();

        return response()->json($items);
    }

    /**
     * Get common teammates for a Pokemon with usage stats
     * GET /api/formats/{format}/pokemon/{pokemon}/teammates
     */
    public function getPokemonTeammates(Request $request, string $format, string $pokemon): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $statsData = $this->fetchStatsData($format);
        $teammates = [];

        if (!empty($statsData['pokemon'])) {
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === strtolower($pokemon) ||
                    strtolower(str_replace(' ', '-', $name)) === strtolower($pokemon)) {
                    $teammates = $stats['teammates'] ?? [];
                    break;
                }
            }
        }

        // Convert to array format and sort by usage
        $result = collect($teammates)
            ->map(function ($usage, $name) {
                return [
                    'name' => $name,
                    'usage' => $usage,
                ];
            })
            ->sortByDesc('usage')
            ->take($limit)
            ->values();

        // Try to get sprites for teammates
        $teammateNames = $result->pluck('name')->toArray();
        $pokemonByName = $this->findPokemonByNames($teammateNames);

        $result = $result->map(function ($teammate) use ($pokemonByName) {
            $normalizedName = $this->normalizePokemonName($teammate['name']);
            $pokemonData = $pokemonByName->get($normalizedName);

            // Use database sprite if available, otherwise fall back to Showdown CDN
            $sprite = $pokemonData?->sprite_front_default ?? $this->getShowdownSpriteUrl($teammate['name']);

            return array_merge($teammate, [
                'sprite' => $sprite,
                'api_id' => $pokemonData?->api_id,
                'types' => $pokemonData?->types?->pluck('name')->toArray() ?? [],
            ]);
        });

        return response()->json($result);
    }

    /**
     * Get counters/checks for a Pokemon with usage stats
     * GET /api/formats/{format}/pokemon/{pokemon}/counters
     */
    public function getPokemonCounters(Request $request, string $format, string $pokemon): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $statsData = $this->fetchStatsData($format);
        $counters = [];

        if (!empty($statsData['pokemon'])) {
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === strtolower($pokemon) ||
                    strtolower(str_replace(' ', '-', $name)) === strtolower($pokemon)) {
                    // Smogon stats use 'checks and counters' format
                    $counters = $stats['counters'] ?? $stats['checks'] ?? [];
                    break;
                }
            }
        }

        // Convert to array format - counters have [ko%, switch%, usage] format
        $result = collect($counters)
            ->map(function ($data, $name) {
                // Data format: [number1, number2, number3] or just a number
                if (is_array($data)) {
                    return [
                        'name' => $name,
                        'score' => $data[0] ?? 0, // KO percentage or score
                        'switch_rate' => $data[1] ?? null,
                        'usage' => $data[2] ?? $data[0] ?? 0,
                    ];
                }
                return [
                    'name' => $name,
                    'score' => $data,
                    'switch_rate' => null,
                    'usage' => $data,
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        // Get sprites for counters
        $counterNames = $result->pluck('name')->toArray();
        $pokemonByName = $this->findPokemonByNames($counterNames);

        $result = $result->map(function ($counter) use ($pokemonByName) {
            $normalizedName = $this->normalizePokemonName($counter['name']);
            $pokemonData = $pokemonByName->get($normalizedName);

            // Use database sprite if available, otherwise fall back to Showdown CDN
            $sprite = $pokemonData?->sprite_front_default ?? $this->getShowdownSpriteUrl($counter['name']);

            return array_merge($counter, [
                'sprite' => $sprite,
                'api_id' => $pokemonData?->api_id,
                'types' => $pokemonData?->types?->pluck('name')->toArray() ?? [],
            ]);
        });

        return response()->json($result);
    }

    /**
     * Get EV spreads for a Pokemon with usage stats
     * GET /api/formats/{format}/pokemon/{pokemon}/spreads
     */
    public function getPokemonSpreads(Request $request, string $format, string $pokemon): JsonResponse
    {
        $limit = $request->input('limit', 20);
        $statsData = $this->fetchStatsData($format);
        $spreads = [];

        if (!empty($statsData['pokemon'])) {
            foreach ($statsData['pokemon'] as $name => $stats) {
                if (strtolower($name) === strtolower($pokemon) ||
                    strtolower(str_replace(' ', '-', $name)) === strtolower($pokemon)) {
                    $spreads = $stats['spreads'] ?? [];
                    break;
                }
            }
        }

        // Parse spreads - format is "Nature:HP/Atk/Def/SpA/SpD/Spe" => usage
        $result = collect($spreads)
            ->map(function ($usage, $spreadString) {
                // Parse "Jolly:0/252/0/0/4/252" format
                $parts = explode(':', $spreadString);
                $nature = $parts[0] ?? 'Hardy';
                $evString = $parts[1] ?? '0/0/0/0/0/0';
                $evParts = explode('/', $evString);

                return [
                    'spread_string' => $spreadString,
                    'nature' => $nature,
                    'evs' => [
                        'hp' => (int)($evParts[0] ?? 0),
                        'atk' => (int)($evParts[1] ?? 0),
                        'def' => (int)($evParts[2] ?? 0),
                        'spa' => (int)($evParts[3] ?? 0),
                        'spd' => (int)($evParts[4] ?? 0),
                        'spe' => (int)($evParts[5] ?? 0),
                    ],
                    'usage' => $usage,
                ];
            })
            ->sortByDesc('usage')
            ->take($limit)
            ->values();

        return response()->json($result);
    }

    /**
     * Get all natures (static data)
     * GET /api/natures
     */
    public function getNatures(): JsonResponse
    {
        $natures = [
            ['name' => 'Hardy', 'increased' => null, 'decreased' => null],
            ['name' => 'Lonely', 'increased' => 'Attack', 'decreased' => 'Defense'],
            ['name' => 'Brave', 'increased' => 'Attack', 'decreased' => 'Speed'],
            ['name' => 'Adamant', 'increased' => 'Attack', 'decreased' => 'Sp. Atk'],
            ['name' => 'Naughty', 'increased' => 'Attack', 'decreased' => 'Sp. Def'],
            ['name' => 'Bold', 'increased' => 'Defense', 'decreased' => 'Attack'],
            ['name' => 'Docile', 'increased' => null, 'decreased' => null],
            ['name' => 'Relaxed', 'increased' => 'Defense', 'decreased' => 'Speed'],
            ['name' => 'Impish', 'increased' => 'Defense', 'decreased' => 'Sp. Atk'],
            ['name' => 'Lax', 'increased' => 'Defense', 'decreased' => 'Sp. Def'],
            ['name' => 'Timid', 'increased' => 'Speed', 'decreased' => 'Attack'],
            ['name' => 'Hasty', 'increased' => 'Speed', 'decreased' => 'Defense'],
            ['name' => 'Serious', 'increased' => null, 'decreased' => null],
            ['name' => 'Jolly', 'increased' => 'Speed', 'decreased' => 'Sp. Atk'],
            ['name' => 'Naive', 'increased' => 'Speed', 'decreased' => 'Sp. Def'],
            ['name' => 'Modest', 'increased' => 'Sp. Atk', 'decreased' => 'Attack'],
            ['name' => 'Mild', 'increased' => 'Sp. Atk', 'decreased' => 'Defense'],
            ['name' => 'Quiet', 'increased' => 'Sp. Atk', 'decreased' => 'Speed'],
            ['name' => 'Bashful', 'increased' => null, 'decreased' => null],
            ['name' => 'Rash', 'increased' => 'Sp. Atk', 'decreased' => 'Sp. Def'],
            ['name' => 'Calm', 'increased' => 'Sp. Def', 'decreased' => 'Attack'],
            ['name' => 'Gentle', 'increased' => 'Sp. Def', 'decreased' => 'Defense'],
            ['name' => 'Sassy', 'increased' => 'Sp. Def', 'decreased' => 'Speed'],
            ['name' => 'Careful', 'increased' => 'Sp. Def', 'decreased' => 'Sp. Atk'],
            ['name' => 'Quirky', 'increased' => null, 'decreased' => null],
        ];

        return response()->json($natures);
    }
}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    /**
     * Base URL for Smogon sets data
     */
    private const SMOGON_SETS_URL = 'https://pkmn.github.io/smogon/data/sets';

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
    // Helper Methods
    // =========================================================================

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
}

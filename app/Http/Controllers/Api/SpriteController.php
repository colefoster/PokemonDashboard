<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SpriteController extends Controller
{
    /**
     * Base URL for raw GitHub sprites
     */
    private const GITHUB_RAW_URL = 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites';

    /**
     * Get Pokemon sprite URL by ID
     * GET /api/sprites/pokemon/{id}
     *
     * Query params:
     * - variant: front (default), back
     * - shiny: false (default), true
     * - female: false (default), true
     * - style: default, official-artwork, home, dream-world, showdown
     * - generation: i, ii, iii, iv, v, vi, vii, viii (for version-specific sprites)
     * - game: specific game name (e.g., red-blue, crystal, firered-leafgreen)
     * - redirect: false (default) returns JSON with URL, true redirects to image
     */
    public function pokemon(Request $request, int $id): JsonResponse|RedirectResponse
    {
        return $this->buildPokemonResponse($request, $id);
    }

    /**
     * Get Pokemon sprite URL by name
     * GET /api/sprites/pokemon/name/{name}
     *
     * Supports various name formats:
     * - Simple: pikachu, bulbasaur
     * - Hyphenated: tapu-koko, mr-mime
     * - Forms: charizard-mega-x, pikachu-gmax
     * - Regional: raichu-alola, meowth-galar
     */
    public function pokemonByName(Request $request, string $name): JsonResponse|RedirectResponse
    {
        $resolution = $this->resolveNameToId($name);

        if (!$resolution['found']) {
            return response()->json([
                'error' => 'Pokemon not found',
                'name' => $name,
                'normalized' => $resolution['normalized'],
                'suggestions' => $resolution['suggestions'] ?? [],
            ], 404);
        }

        return $this->buildPokemonResponse($request, $resolution['id'], $resolution['name'], $resolution['showdown_name']);
    }

    /**
     * Build the Pokemon sprite response (shared by ID and name lookups)
     */
    private function buildPokemonResponse(
        Request $request,
        int $id,
        ?string $name = null,
        ?string $showdownName = null
    ): JsonResponse|RedirectResponse {
        $variant = $request->query('variant', 'front');
        $shiny = filter_var($request->query('shiny', false), FILTER_VALIDATE_BOOLEAN);
        $female = filter_var($request->query('female', false), FILTER_VALIDATE_BOOLEAN);
        $style = $request->query('style', 'default');
        $generation = $request->query('generation');
        $game = $request->query('game');
        $redirect = filter_var($request->query('redirect', false), FILTER_VALIDATE_BOOLEAN);

        $url = $this->buildPokemonSpriteUrl($id, $variant, $shiny, $female, $style, $generation, $game, $showdownName);

        if ($redirect) {
            return redirect()->away($url);
        }

        $response = [
            'id' => $id,
            'url' => $url,
            'options' => [
                'variant' => $variant,
                'shiny' => $shiny,
                'female' => $female,
                'style' => $style,
                'generation' => $generation,
                'game' => $game,
            ],
        ];

        if ($name) {
            $response['name'] = $name;
        }
        if ($showdownName) {
            $response['showdown_name'] = $showdownName;
        }

        return response()->json($response);
    }

    /**
     * Resolve a Pokemon name to its API ID
     * Returns array with 'found', 'id', 'name', 'showdown_name', 'normalized', 'suggestions'
     */
    private function resolveNameToId(string $name): array
    {
        $normalized = $this->normalizePokemonName($name);

        // Check cache first
        $cacheKey = "pokemon_name_to_id:{$normalized}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Try exact match first
        $pokemon = Pokemon::whereRaw('LOWER(name) = ?', [$normalized])->first();

        if ($pokemon) {
            $result = [
                'found' => true,
                'id' => $pokemon->api_id,
                'name' => $pokemon->getAttributes()['name'], // Get raw name without accessor
                'showdown_name' => $this->toShowdownName($pokemon->getAttributes()['name']),
                'normalized' => $normalized,
            ];
            Cache::put($cacheKey, $result, 3600);
            return $result;
        }

        // Try fuzzy match with common transformations
        $pokemon = $this->fuzzyMatchPokemon($normalized);

        if ($pokemon) {
            $result = [
                'found' => true,
                'id' => $pokemon->api_id,
                'name' => $pokemon->getAttributes()['name'],
                'showdown_name' => $this->toShowdownName($pokemon->getAttributes()['name']),
                'normalized' => $normalized,
            ];
            Cache::put($cacheKey, $result, 3600);
            return $result;
        }

        // Not found - get suggestions
        $suggestions = Pokemon::whereRaw('LOWER(name) LIKE ?', ["%{$normalized}%"])
            ->limit(5)
            ->pluck('name')
            ->map(fn($n) => strtolower($n))
            ->toArray();

        return [
            'found' => false,
            'normalized' => $normalized,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Normalize a Pokemon name for database lookup
     */
    private function normalizePokemonName(string $name): string
    {
        $name = strtolower(trim($name));
        // Replace spaces and underscores with hyphens
        $name = preg_replace('/[\s_]+/', '-', $name);
        // Remove any non-alphanumeric characters except hyphens
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);
        // Collapse multiple hyphens
        $name = preg_replace('/-+/', '-', $name);
        return trim($name, '-');
    }

    /**
     * Convert database name to Pokemon Showdown format
     * Database: "tapu-koko" -> Showdown: "tapukoko"
     */
    private function toShowdownName(string $name): string
    {
        // Showdown uses lowercase with no hyphens for most Pokemon
        $showdown = strtolower(str_replace('-', '', $name));

        // Special cases for forms that Showdown handles differently
        $formMappings = [
            'megax' => '-mega-x',
            'megay' => '-mega-y',
            'gmax' => '-gmax',
            'alola' => '-alola',
            'galar' => '-galar',
            'hisui' => '-hisui',
            'paldea' => '-paldea',
        ];

        foreach ($formMappings as $search => $replace) {
            if (str_ends_with($showdown, $search)) {
                $base = substr($showdown, 0, -strlen($search));
                $showdown = $base . $replace;
                break;
            }
        }

        return $showdown;
    }

    /**
     * Try fuzzy matching for Pokemon names
     */
    private function fuzzyMatchPokemon(string $normalized): ?Pokemon
    {
        // Common name variations to try
        $variations = [
            $normalized,
            str_replace('-', '', $normalized), // Remove all hyphens
            preg_replace('/(mega|gmax|alola|galar|hisui|paldea)$/', '-$1', $normalized), // Add hyphen before form
        ];

        // Special character handling (Mr. Mime, etc.)
        if (str_starts_with($normalized, 'mr')) {
            $variations[] = 'mr-' . substr($normalized, 2);
            $variations[] = 'mr.-' . substr($normalized, 2);
        }
        if (str_starts_with($normalized, 'mime')) {
            $variations[] = 'mr-mime' . substr($normalized, 4);
            $variations[] = 'mime-jr' . substr($normalized, 4);
        }

        foreach ($variations as $variant) {
            $pokemon = Pokemon::whereRaw('LOWER(name) = ?', [$variant])->first();
            if ($pokemon) {
                return $pokemon;
            }
        }

        // Try partial match at start
        return Pokemon::whereRaw('LOWER(name) LIKE ?', ["{$normalized}%"])->first();
    }

    /**
     * Get item sprite URL by name
     * GET /api/sprites/items/{name}
     */
    public function item(Request $request, string $name): JsonResponse|RedirectResponse
    {
        $name = strtolower($name);
        $redirect = filter_var($request->query('redirect', false), FILTER_VALIDATE_BOOLEAN);
        $url = self::GITHUB_RAW_URL . "/items/{$name}.png";

        if ($redirect) {
            return redirect()->away($url);
        }

        return response()->json([
            'name' => $name,
            'url' => $url,
        ]);
    }

    /**
     * Get type sprite URL
     * GET /api/sprites/types/{name}
     *
     * Query params:
     * - generation: iii, iv, v, vi, vii, viii, ix (defaults to ix)
     */
    public function type(Request $request, string $name): JsonResponse|RedirectResponse
    {
        $generation = $request->query('generation', 'ix');
        $name = strtolower($name);
        $redirect = filter_var($request->query('redirect', false), FILTER_VALIDATE_BOOLEAN);
        $url = self::GITHUB_RAW_URL . "/types/generation-{$generation}/{$name}.png";

        if ($redirect) {
            return redirect()->away($url);
        }

        return response()->json([
            'name' => $name,
            'generation' => $generation,
            'url' => $url,
        ]);
    }

    /**
     * Get badge sprite URL
     * GET /api/sprites/badges/{name}
     */
    public function badge(Request $request, string $name): JsonResponse|RedirectResponse
    {
        $name = strtolower($name);
        $redirect = filter_var($request->query('redirect', false), FILTER_VALIDATE_BOOLEAN);
        $url = self::GITHUB_RAW_URL . "/badges/{$name}.png";

        if ($redirect) {
            return redirect()->away($url);
        }

        return response()->json([
            'name' => $name,
            'url' => $url,
        ]);
    }

    /**
     * List available Pokemon sprite styles
     * GET /api/sprites/pokemon/styles
     */
    public function pokemonStyles(): JsonResponse
    {
        return response()->json([
            'default' => 'Standard game sprites',
            'official-artwork' => 'Ken Sugimori official artwork',
            'home' => 'Pokemon HOME 3D renders',
            'dream-world' => 'Dream World artwork (Gen 5)',
            'showdown' => 'Pokemon Showdown animated sprites',
        ]);
    }

    /**
     * List available generations for version-specific sprites
     * GET /api/sprites/pokemon/generations
     */
    public function pokemonGenerations(): JsonResponse
    {
        return response()->json([
            'i' => ['red-blue', 'yellow'],
            'ii' => ['gold', 'silver', 'crystal'],
            'iii' => ['ruby-sapphire', 'emerald', 'firered-leafgreen'],
            'iv' => ['diamond-pearl', 'platinum', 'heartgold-soulsilver'],
            'v' => ['black-white'],
            'vi' => ['x-y', 'omegaruby-alphasapphire'],
            'vii' => ['ultra-sun-ultra-moon', 'icons'],
            'viii' => ['icons'],
        ]);
    }

    /**
     * Get multiple Pokemon sprites at once
     * GET /api/sprites/pokemon/batch?ids=1,4,7,25
     */
    public function pokemonBatch(Request $request): JsonResponse
    {
        $ids = $request->query('ids', '');
        $variant = $request->query('variant', 'front');
        $shiny = filter_var($request->query('shiny', false), FILTER_VALIDATE_BOOLEAN);
        $female = filter_var($request->query('female', false), FILTER_VALIDATE_BOOLEAN);
        $style = $request->query('style', 'default');

        $idList = array_filter(array_map('intval', explode(',', $ids)));

        $sprites = [];
        foreach ($idList as $id) {
            $sprites[$id] = $this->buildPokemonSpriteUrl($id, $variant, $shiny, $female, $style, null, null);
        }

        return response()->json([
            'sprites' => $sprites,
            'options' => [
                'variant' => $variant,
                'shiny' => $shiny,
                'female' => $female,
                'style' => $style,
            ],
        ]);
    }

    /**
     * Build the GitHub URL for a Pokemon sprite based on options
     */
    private function buildPokemonSpriteUrl(
        int $id,
        string $variant,
        bool $shiny,
        bool $female,
        string $style,
        ?string $generation,
        ?string $game,
        ?string $showdownName = null
    ): string {
        $basePath = self::GITHUB_RAW_URL . '/pokemon';

        // Handle version-specific sprites
        if ($generation && $game) {
            return $this->buildVersionSpriteUrl($basePath, $id, $generation, $game, $variant, $shiny);
        }

        // Handle special styles (official-artwork, home, dream-world, showdown)
        if ($style !== 'default') {
            return $this->buildStyleSpriteUrl($basePath, $id, $style, $variant, $shiny, $female, $showdownName);
        }

        // Build default sprite URL
        $pathParts = [$basePath];

        if ($variant === 'back') {
            $pathParts[] = 'back';
        }

        if ($shiny) {
            $pathParts[] = 'shiny';
        }

        if ($female) {
            $pathParts[] = 'female';
        }

        $pathParts[] = "{$id}.png";

        return implode('/', $pathParts);
    }

    /**
     * Build URL for special style sprites (official-artwork, home, etc.)
     */
    private function buildStyleSpriteUrl(
        string $basePath,
        int $id,
        string $style,
        string $variant,
        bool $shiny,
        bool $female,
        ?string $showdownName = null
    ): string {
        $pathParts = [$basePath, 'other', $style];

        // Showdown has back sprites
        if ($style === 'showdown' && $variant === 'back') {
            $pathParts[] = 'back';
        }

        if ($shiny) {
            $pathParts[] = 'shiny';
        }

        if ($female) {
            $pathParts[] = 'female';
        }

        // Different styles use different extensions and naming
        $extension = match ($style) {
            'showdown' => 'gif',
            default => 'png',
        };

        // Showdown sprites use names instead of IDs
        $filename = ($style === 'showdown' && $showdownName)
            ? $showdownName
            : (string) $id;

        $pathParts[] = "{$filename}.{$extension}";

        return implode('/', $pathParts);
    }

    /**
     * Build URL for version-specific sprites
     */
    private function buildVersionSpriteUrl(
        string $basePath,
        int $id,
        string $generation,
        string $game,
        string $variant,
        bool $shiny
    ): string {
        $pathParts = [$basePath, 'versions', "generation-{$generation}", $game];

        if ($variant === 'back') {
            $pathParts[] = 'back';
        }

        if ($shiny) {
            $pathParts[] = 'shiny';
        }

        $pathParts[] = "{$id}.png";

        return implode('/', $pathParts);
    }
}
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
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

        // If no per_page specified, return all Pokemon
        if (!$perPage) {
            $pokemon = $query->get();
            return response()->json($pokemon);
        }

        $pokemon = $query->paginate($perPage);

        return response()->json($pokemon);
    }

    /** Get a list all Pokemon within a Smogon tier with their types and stats */
    public function setsByFormat(string $tierId): JsonResponse
    {
        return JsonResponse::fromJsonString(Http::get("https://pkmn.github.io/smogon/data/sets/${tierId}.json"));
    }
    public function setsByGen(string $gen): JsonResponse
    {
        if (str_contains(strtolower($gen),"gen")){
            return JsonResponse::fromJsonString(Http::get("https://pkmn.github.io/smogon/data/sets/${gen}.json"));
        }
        else{
            return JsonResponse::fromJsonString(Http::get("https://pkmn.github.io/smogon/data/sets/gen${gen}.json"));

        }
    }

    /**
     * Get a single Pokemon with full details
     */
    public function show(int $apiId): JsonResponse
    {
        $pokemon = Pokemon::with([
            'types',
            'abilities',
            'moves',
            'stats',
            'species.evolutionChain.evolutions'
        ])
            ->where('api_id', $apiId)
            ->firstOrFail();

        return response()->json($pokemon);
    }

    /**
     * Search Pokemon by name or ID
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
            ->limit(10)
            ->get();

        return response()->json($pokemon);
    }
}

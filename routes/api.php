<?php

use App\Http\Controllers\Api\PokemonController;
use App\Http\Controllers\Api\SpriteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Pokemon API routes (database)
Route::prefix('pokemon')->group(function () {
    Route::get('/', [PokemonController::class, 'index']);
    Route::get('/search', [PokemonController::class, 'search']);
    Route::get('/{apiId}', [PokemonController::class, 'show'])->where('apiId', '[0-9]+');
});

// Format/Smogon API routes
Route::prefix('formats/{format}')->group(function () {
    // Sets endpoints (Smogon data only)
    Route::get('/sets', [PokemonController::class, 'getSets']);
    Route::get('/sets/search', [PokemonController::class, 'searchSets']);

    // Names endpoint
    Route::get('/names', [PokemonController::class, 'getNames']);

    // Pokemon endpoints (database data for Pokemon in format)
    Route::get('/pokemon', [PokemonController::class, 'getPokemonInFormat']);
    Route::get('/pokemon/search', [PokemonController::class, 'searchPokemonInFormat']);

    // Combined endpoints (sets + database data)
    Route::get('/combined', [PokemonController::class, 'getCombined']);
    Route::get('/combined/search', [PokemonController::class, 'searchCombined']);
});

// Sprite API routes (wraps GitHub raw URLs from PokeAPI/sprites)
Route::prefix('sprites')->group(function () {
    // Pokemon sprites
    Route::get('/pokemon/styles', [SpriteController::class, 'pokemonStyles']);
    Route::get('/pokemon/generations', [SpriteController::class, 'pokemonGenerations']);
    Route::get('/pokemon/batch', [SpriteController::class, 'pokemonBatch']);
    Route::get('/pokemon/name/{name}', [SpriteController::class, 'pokemonByName']);
    Route::get('/pokemon/{id}', [SpriteController::class, 'pokemon'])->where('id', '[0-9]+');

    // Item sprites
    Route::get('/items/{name}', [SpriteController::class, 'item']);

    // Type sprites
    Route::get('/types/{name}', [SpriteController::class, 'type']);

    // Badge sprites
    Route::get('/badges/{name}', [SpriteController::class, 'badge']);
});

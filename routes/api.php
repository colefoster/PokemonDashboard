<?php

use App\Http\Controllers\Api\PokemonController;
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


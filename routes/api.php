<?php

use App\Http\Controllers\Api\PokemonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Pokemon API routes
Route::prefix('pokemon')->group(function () {
    Route::get('/', [PokemonController::class, 'index']);
    Route::get('/search', [PokemonController::class, 'search']);
    Route::get('/{apiId}', [PokemonController::class, 'show']);
});

Route::prefix('sets')->group(function () {

    Route::get('/format/{format}', [PokemonController::class, 'setsByFormat']);

    Route::get('/gen/{gen}', [PokemonController::class, 'setsByGen']);
});


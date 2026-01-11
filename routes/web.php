<?php

use App\Http\Controllers\TeambuilderController;
use Illuminate\Support\Facades\Route;

// Team Builder (Vue standalone page)
Route::get('/teambuilder', [TeambuilderController::class, 'index'])
    ->name('teambuilder');

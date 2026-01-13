<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/teambuilder', function () {
    return Inertia::render('Welcome');
})->name('teambuilder');
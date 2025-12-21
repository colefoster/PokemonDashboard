<?php

namespace App\Filament\Resources\Moves\Schemas\Components;

use App\Livewire\MovePokemonTable;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;

class LearnedByPokemonSection
{
    public static function make(): Section
    {
        return Section::make('Pokémon That Can Learn This Move')
            ->schema([
                Livewire::make(MovePokemonTable::class, fn($record) => ['move' => $record])
            ])
            ->collapsible()
            ->collapsed(false);
    }
}

<?php

namespace App\Filament\Resources\Pokemon\Schemas;

use App\Filament\Resources\Pokemon\Schemas\Components\EvolutionsSection;
use App\Filament\Resources\Pokemon\Schemas\Components\SpeciesDetailsSection;
use App\Filament\Resources\Pokemon\Schemas\Components\SpriteViewerSection;
use App\Livewire\PokemonMovesTable;
use App\Livewire\PokemonStatsRadarChart;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class PokemonInfolist
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpeciesDetailsSection::make()
                    ->columnSpan([
                        'sm' => 'full',
                        'md' => 'full',
                        'lg' => 1,
                    ]),
                Grid::make(1)
                    ->schema([
                        SpriteViewerSection::make(),
                        Livewire::make(PokemonStatsRadarChart::class, fn($record) => ['record' => $record]),
                    ])
                    ->columnSpan([
                        'sm' => 'full',
                        'md' => 'full',
                        'lg' => 1,
                    ]),

                EvolutionsSection::make()
                    ->columnSpanFull(),

                Livewire::make(PokemonMovesTable::class, fn($record) => ['pokemon' => $record])
                    ->columnSpanFull()
            ])
            ->columns([
                'sm' => 1,
                'md' => 1,
                'lg' => 2,
            ]);
    }
}

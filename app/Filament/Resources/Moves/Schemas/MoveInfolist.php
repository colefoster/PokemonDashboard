<?php

namespace App\Filament\Resources\Moves\Schemas;

use App\Filament\Resources\Moves\Schemas\Components\MoveDescriptionSection;
use App\Filament\Resources\Moves\Schemas\Components\MoveDetailsSection;
use App\Filament\Resources\Moves\Schemas\Components\LearnedByPokemonSection;
use Filament\Schemas\Schema;

class MoveInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                MoveDetailsSection::make()
                    ->columnSpanFull(),
                MoveDescriptionSection::make()
                    ->columnSpanFull(),
                LearnedByPokemonSection::make()
                    ->columnSpanFull(),
            ]);
    }
}

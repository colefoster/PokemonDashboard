<?php

namespace App\Filament\Resources\Pokemon\Schemas\Components;

use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;

class SpriteViewerSection
{
    public static function make(): Fieldset
    {
        return Fieldset::make('Sprites')
            ->schema([
                Tabs::make('sprite_tabs')
                    ->contained(true)
                    ->tabs([
                        Tabs\Tab::make('Regular')
                            ->schema([

                                ImageEntry::make('sprite_front_default')
                                    ->label('Front')
                                    ->alignCenter(),

                                ImageEntry::make('sprite_back_default')
                                    ->label('Back')
                                    ->alignCenter(),
                            ]),

                        Tabs\Tab::make('Shiny')
                            ->schema([

                                        ImageEntry::make('sprite_front_shiny')
                                            ->label('Front')
                                            ->alignCenter(),

                                        ImageEntry::make('sprite_back_shiny')
                                            ->label('Back')
                                            ->alignCenter(),
                                    ]),
                            ]),
            ])
            ->columns(1);
    }
}

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
                    ->contained(false)
                    ->tabs([
                        Tabs\Tab::make('Regular')
                            ->schema([
                                Fieldset::make('regular_sprites')
                                    ->hiddenLabel()
                                    ->columns(2)
                                    ->schema([
                                        ImageEntry::make('sprite_front_default')
                                            ->label('Front')
                                            ->alignCenter()
                                            ->extraImgAttributes(['style' => 'image-rendering: pixelated;']),

                                        ImageEntry::make('sprite_back_default')
                                            ->label('Back')
                                            ->alignCenter()
                                            ->extraImgAttributes(['style' => 'image-rendering: pixelated;']),
                                    ]),
                            ]),

                        Tabs\Tab::make('Shiny')
                            ->schema([
                                Fieldset::make('shiny_sprites')
                                    ->hiddenLabel()
                                    ->columns(2)
                                    ->schema([
                                        ImageEntry::make('sprite_front_shiny')
                                            ->label('Front')
                                            ->alignCenter()
                                            ->extraImgAttributes(['style' => 'image-rendering: pixelated;']),

                                        ImageEntry::make('sprite_back_shiny')
                                            ->label('Back')
                                            ->alignCenter()
                                            ->extraImgAttributes(['style' => 'image-rendering: pixelated;']),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
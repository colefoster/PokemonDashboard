<?php

namespace App\Filament\Resources\Pokemon\Schemas\Components;

use App\Filament\Resources\Pokemon\PokemonResource;
use App\Models\Pokemon;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\TextSize;

class EvolutionsSection
{
    public static function make(): Fieldset
    {

        return FieldSet::make('Evolution Chain')
            ->hidden(fn($record) => !($record->getEvolutionChainData()['has_evolutions'] ?? false))
            ->columns(fn($record) => [
                'default' => $record->getEvolutionChainData()['stage_count'] + round($record->getEvolutionChainData()['stage_count'] / 2),
                'sm' => $record->getEvolutionChainData()['stage_count'] + round($record->getEvolutionChainData()['stage_count'] / 2),
                'md' => $record->getEvolutionChainData()['stage_count'] + round($record->getEvolutionChainData()['stage_count'] / 2),
                'lg' => $record->getEvolutionChainData()['stage_count'] + round($record->getEvolutionChainData()['stage_count'] / 2),
            ])
            ->schema([
                // Stage 1

                Fieldset::make('stage_1_fieldset')
                    ->contained(false)
                    ->hiddenLabel()
                    ->columns(
                        [
                            'default' => 1,
                            'sm' => 1,
                            'md' => 1,
                            'lg' => 1,
                        ]
                    )
                    ->schema([
                        TextEntry::make('stage_1_label')
                            ->hiddenLabel()
                            ->default(fn($record) => $record->getEvolutionChainData()['stage_1_name'])
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->alignCenter(),

                        ImageEntry::make('stage_1_sprite')
                            ->hiddenLabel()
                            ->state(fn($record) => $record->getEvolutionChainData()['stage_1_sprite'] ?? null)
                            ->url(fn($record) => PokemonResource::getUrl('view', [
                                'record' => Pokemon::where('api_id', $record->getEvolutionChainData()['stage_1_api_id'])->first()
                            ]))
                            ->alignCenter()
                            ->defaultImageUrl(url('/images/sprite-placeholder.png')),
                    ]),

                TextEntry::make('stage_1_method')
                    ->hiddenLabel()
                    ->state(fn($record) => $record->getEvolutionChainData()['stage_1_method'] ?? null)
                    ->hidden(fn($record) => empty($record->getEvolutionChainData()['stage_1_method'] ?? null))
                    ->badge()
                    ->alignCenter()
                    ->size(TextSize::Large)
                    ->color('info')
                    ->extraEntryWrapperAttributes(['style' => 'display: flex; align-items: center; justify-content: center; height: 100%;']),

                Fieldset::make('stage_2_fieldset')
                    ->contained(false)
                    ->hiddenLabel()
                    ->hidden(fn($record) => ($record->getEvolutionChainData()['stage_count'] ?? 0) < 2)
                    ->columns(
                        [
                            'default' => 1,
                            'sm' => 1,
                            'md' => 1,
                            'lg' => 1,
                        ]
                    )
                    ->schema([
                        TextEntry::make('stage_2_label')
                            ->default(fn($record) => $record->getEvolutionChainData()['stage_2_name'])
                            ->hiddenLabel()
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->alignCenter(),
                        ImageEntry::make('stage_2_sprite')
                            ->hiddenLabel()
                            ->hidden(fn($record) => ($record->getEvolutionChainData()['stage_count'] ?? 0) < 2)
                            ->state(fn($record) => $record->getEvolutionChainData()['stage_2_sprite'] ?? null)
                            ->url(fn($record) => PokemonResource::getUrl('view', [
                                'record' => Pokemon::where('api_id', $record->getEvolutionChainData()['stage_2_api_id'])->first()
                            ]))
                            ->alignCenter()
                            ->defaultImageUrl(url('/images/sprite-placeholder.png'))

                    ]),

                TextEntry::make('stage_2_method')
                    ->hiddenLabel()
                    ->state(fn($record) => $record->getEvolutionChainData()['stage_2_method'] ?? null)
                    ->hidden(fn($record) => empty($record->getEvolutionChainData()['stage_2_method'] ?? null))
                    ->badge()
                    ->size(TextSize::Large)
                    ->color('info')
                    ->alignCenter()
                    ->extraEntryWrapperAttributes(['style' => 'display: flex; align-items: center; justify-content: center; height: 100%;']),

                Fieldset::make('stage_3_fieldset')
                    ->contained(false)
                    ->hiddenLabel()
                    ->hidden(fn($record) => ($record->getEvolutionChainData()['stage_count'] ?? 0) < 3)
                    ->columns(
                        [
                            'default' => 1,
                            'sm' => 1,
                            'md' => 1,
                            'lg' => 1,
                        ]
                    )
                    ->schema([
                        TextEntry::make('stage_3_label')
                            ->hiddenLabel()
                            ->default(fn($record) => $record->getEvolutionChainData()['stage_3_name'])
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->alignCenter(),

                        ImageEntry::make('stage_3_sprite')
                            ->hiddenLabel()
                            ->state(fn($record) => $record->getEvolutionChainData()['stage_3_sprite'] ?? null)
                            ->hidden(fn($record) => ($record->getEvolutionChainData()['stage_count'] ?? 0) < 3)
                            ->url(fn($record) => PokemonResource::getUrl('view', [
                                'record' => Pokemon::where('api_id', $record->getEvolutionChainData()['stage_3_api_id'])->first()
                            ]))
                            ->alignCenter()
                            ->defaultImageUrl(url('/images/sprite-placeholder.png')),
                    ]),

            ]);
    }
}

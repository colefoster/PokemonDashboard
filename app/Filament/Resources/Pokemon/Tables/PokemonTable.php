<?php

namespace App\Filament\Resources\Pokemon\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Filters\TypesFilter;

class PokemonTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('api_id', 'asc')
            ->columns([
                ColumnGroup::make('Basic Info', [
                    TextColumn::make('api_id')
                        ->numeric()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('name')
                        ->formatStateUsing(function ($state) {
                            return str_replace(" ", "-", (ucwords(str_replace("-", " ", $state))));
                        })
                        ->searchable()
                        ->sortable()
                        ->width(50),
                    ImageColumn::make('sprite_front_default')
                        ->label('Sprite')
                        ->defaultImageUrl(asset('/images/3x-sprite-placeholder.png'))
                        ->imageSize("20")
                        ->toggleable()
                        ->extraImgAttributes([
                            'class' => 'pixelated',
                            'style' => 'image-rendering: pixelated; image-rendering: -moz-crisp-edges; image-rendering: crisp-edges;',
                        ])
                        ->alignCenter(),
                    TextColumn::make('types.name')
                        ->label('Type')
                        ->toggleable()
                        ->alignCenter()
                        ->badge()
                        ->color(fn($state): string => $state)
                        ->formatStateUsing(fn($state) => ucfirst($state)),
                    TextColumn::make('height')
                        ->numeric()
                        ->toggleable()
                        ->formatStateUsing(fn($state) => number_format($state, 1). " m")
                        ->sortable(),
                    TextColumn::make('weight')
                        ->numeric()
                        ->toggleable()
                        ->formatStateUsing(fn($state) => number_format($state, 1) . " kg")
                        ->sortable(),
                    TextColumn::make('species.generation')
                        ->label('Gen')
                        ->badge()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->formatStateUsing(function ($state) {
                            $gens = [
                                "generation-i" => "Gen 1",
                                "generation-ii" => "Gen 2",
                                "generation-iii" => "Gen 3",
                                "generation-iv" => "Gen 4",
                                "generation-v" => "Gen 5",
                                "generation-vi" => "Gen 6",
                                "generation-vii" => "Gen 7",
                                "generation-viii" => "Gen 8",
                                "generation-ix" => "Gen 9",
                                "generation-x" => "Gen 10",
                            ];
                            return $gens[$state];
                        })
                        ->sortable()
                ]),

                ColumnGroup::make('Stats', [
                    TextColumn::make('total_base_stat')
                        ->label('BST')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as bst_stats', 'pokemon.id', '=', 'bst_stats.pokemon_id')
                                ->groupBy('pokemon.id')
                                ->orderByRaw("SUM(bst_stats.base_stat) {$direction}")
                                ->select('pokemon.*');
                        }),
                    TextColumn::make('hp_stat')
                        ->label('HP')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as hp_stats', function ($join) {
                                    $join->on('pokemon.id', '=', 'hp_stats.pokemon_id')
                                        ->where('hp_stats.stat_name', '=', 'hp');
                                })
                                ->orderBy('hp_stats.base_stat', $direction)
                                ->select('pokemon.*');
                        }),
                    TextColumn::make('attack_stat')
                        ->label('ATK')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as attack_stats', function ($join) {
                                    $join->on('pokemon.id', '=', 'attack_stats.pokemon_id')
                                        ->where('attack_stats.stat_name', '=', 'attack');
                                })
                                ->orderBy('attack_stats.base_stat', $direction)
                                ->select('pokemon.*');
                        }),
                    TextColumn::make('defense_stat')
                        ->label('DEF')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as defense_stats', function ($join) {
                                    $join->on('pokemon.id', '=', 'defense_stats.pokemon_id')
                                        ->where('defense_stats.stat_name', '=', 'defense');
                                })
                                ->orderBy('defense_stats.base_stat', $direction)
                                ->select('pokemon.*');
                        }),
                    TextColumn::make('special_attack_stat')
                        ->label('SPA')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as sp_atk_stats', function ($join) {
                                    $join->on('pokemon.id', '=', 'sp_atk_stats.pokemon_id')
                                        ->where('sp_atk_stats.stat_name', '=', 'special-attack');
                                })
                                ->orderBy('sp_atk_stats.base_stat', $direction)
                                ->select('pokemon.*');
                        }),
                    TextColumn::make('special_defense_stat')
                        ->label('SPD')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as sp_def_stats', function ($join) {
                                    $join->on('pokemon.id', '=', 'sp_def_stats.pokemon_id')
                                        ->where('sp_def_stats.stat_name', '=', 'special-defense');
                                })
                                ->orderBy('sp_def_stats.base_stat', $direction)
                                ->select('pokemon.*');
                        }),
                    TextColumn::make('speed_stat')
                        ->label('SPE')
                        ->alignCenter()
                        ->numeric()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable(query: function ($query, $direction) {
                            return $query
                                ->leftJoin('pokemon_stats as speed_stats', function ($join) {
                                    $join->on('pokemon.id', '=', 'speed_stats.pokemon_id')
                                        ->where('speed_stats.stat_name', '=', 'speed');
                                })
                                ->orderBy('speed_stats.base_stat', $direction)
                                ->select('pokemon.*');
                        }),
                ]),




//                TextColumn::make('cry_latest')
//                    ->searchable(),
//                TextColumn::make('cry_legacy')
//                    ->searchable(),

            ])
            ->filters([
                TypesFilter::make(3), // Use 2, 3, 4 for columns; 0 for grouped; null for single column
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

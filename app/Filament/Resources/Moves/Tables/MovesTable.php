<?php

namespace App\Filament\Resources\Moves\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MovesTable
{
    public static bool $usePokemonSprites = true;


    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('api_id', 'asc')
            ->columns([
                TextColumn::make('api_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->formatStateUsing(function ($state) {
                        return str_replace(" ", "-", (ucwords(str_replace("-", " ", $state))));
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('power')
                    ->placeholder('-')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('pp')
                    ->label('PP')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('accuracy')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->placeholder('100%')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('priority')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state): string => $state ?? 'gray')
                    ->formatStateUsing(fn($state) => ucfirst($state ?? '-'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('damage_class')
                    ->label('Class')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '-'))
                    ->searchable()
                    ->toggleable(),
                ImageColumn::make('pokemon_sprites')
                    ->label('Pokemon')
                    ->visible(fn() => self::$usePokemonSprites)
                    ->getStateUsing(function ($record) {
                        // Return all pokemon sprites shuffled - let limit() handle the count
                        return $record->pokemon
                            ->shuffle()
                            ->pluck('sprite_front_default')
                            ->filter()
                            ->values()
                            ->toArray();
                    })
                    ->limit(3)
                    ->limitedRemainingText(
                        size: 'md'
                    )
                    ->ring(2)
                    ->imageSize(56)
                    ->extraImgAttributes([
                        'class' => 'rounded-full'
                    ])

                    ->toggleable(),
                TextColumn::make('pokemon.name')
                    ->label('Pokemon')
                    ->visible(fn() => !self::$usePokemonSprites)
                    ->badge()
                    ->color(fn($record) => $record->type?->name ?? 'gray')
                    ->limitList(3)
                    ->formatStateUsing(fn($state) => ucwords(str_replace('-', ' ', $state)))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('pokemon_count')
                    ->label('# Pokemon')
                    ->counts('pokemon')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('generation')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
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
                        return $gens[$state] ?? ucwords(str_replace('-', ' ', $state));
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('effect_chance')
                    ->label('Effect %')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contest_type')
                    ->label('Contest')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ailment')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meta_category')
                    ->label('Category')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('min_hits')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_hits')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('min_turns')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_turns')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('drain')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('healing')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('crit_rate')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ailment_chance')
                    ->label('Ailment %')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('flinch_chance')
                    ->label('Flinch %')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stat_chance')
                    ->label('Stat %')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_pokemon')
                    ->label('Has Pokemon')
                    ->placeholder('All moves')
                    ->trueLabel('With Pokemon')
                    ->falseLabel('Without Pokemon')
                    ->default(true)
                    ->queries(
                        true: fn($query) => $query->has('pokemon', '>', 0),
                        false: fn($query) => $query->has('pokemon', '=', 0),
                    ),
                TrashedFilter::make(),
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

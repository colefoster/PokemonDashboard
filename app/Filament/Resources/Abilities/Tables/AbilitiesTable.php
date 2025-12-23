<?php

namespace App\Filament\Resources\Abilities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AbilitiesTable
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
                ImageColumn::make('pokemon_sprites')
                    ->label('Pokemon')
                    ->visible(fn() => self::$usePokemonSprites)
                    ->getStateUsing(function ($record) {
                        // Store pokemon data as an array of arrays with sprite and URL
                        return $record->pokemon
                            ->filter(fn($pokemon) => $pokemon->sprite_front_default)
                            ->take(5)
                            ->map(fn($pokemon) => [
                                'sprite' => $pokemon->sprite_front_default,
                                'url' => \App\Filament\Resources\Pokemon\PokemonResource::getUrl('view', ['record' => $pokemon]),
                                'name' => $pokemon->name,
                            ])
                            ->values()
                            ->toArray();
                    })
                    ->view('filament.tables.columns.clickable-pokemon-sprites')
                    ->toggleable(),
                TextColumn::make('pokemon.name')
                    ->label('Pokemon')
                    ->visible(fn() => !self::$usePokemonSprites)
                    ->badge()
                    ->limitList(5)
                    ->formatStateUsing(fn($state) => ucwords(str_replace('-', ' ', $state)))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('pokemon_count')
                    ->label('# Pokemon')
                    ->counts('pokemon')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_main_series')
                    ->boolean()
                    ->toggleable(),
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

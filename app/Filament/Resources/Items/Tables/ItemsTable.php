<?php

namespace App\Filament\Resources\Items\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('api_id', 'asc')
            ->columns([
                TextColumn::make('api_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                ImageColumn::make('sprite'),

                TextColumn::make('cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('category')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('-', ' ', ($state))))
                    ->searchable(),
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
                SelectFilter::make('category')
                    ->options(fn () => \App\Models\Item::withoutGlobalScope('ordered')
                        ->select('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->filter()
                    )
                    ->searchable()
                    ->multiple(),

                Filter::make('cost')
                    ->form([
                        TextInput::make('cost_from')
                            ->numeric()
                            ->label('Min Cost')
                            ->placeholder('0'),
                        TextInput::make('cost_to')
                            ->numeric()
                            ->label('Max Cost')
                            ->placeholder('100,000'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['cost_from'] !== null,
                                fn (Builder $query, $value): Builder => $query->where('cost', '>=', $data['cost_from']),
                            )
                            ->when(
                                $data['cost_to'] !== null,
                                fn (Builder $query, $value): Builder => $query->where('cost', '<=', $data['cost_to']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['cost_from'] !== null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Min cost: $' . number_format($data['cost_from']))
                                ->removeField('cost_from');
                        }

                        if ($data['cost_to'] !== null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Max cost: $' . number_format($data['cost_to']))
                                ->removeField('cost_to');
                        }

                        return $indicators;
                    }),

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

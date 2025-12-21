<?php

namespace App\Livewire;

use App\Filament\Resources\Moves\MoveResource;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PokemonMovesTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public $pokemon;

    public function mount($pokemon): void
    {
        $this->pokemon = $pokemon;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->pokemon->moves()
                    ->withPivot('learn_method', 'level_learned_at')
                    ->getQuery()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Move Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => ucwords(str_replace('-', ' ', $state)))
                    ->url(fn($record) => MoveResource::getUrl('view', ['record' => $record])),
                TextColumn::make('type.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state): string => $state)
                    ->sortable(),
                TextColumn::make('damage_class')
                    ->label('Class')
                    ->badge()
                    ->color(fn($state): string => $state)
                    ->sortable(),
                TextColumn::make('power')
                    ->label('Power')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('pp')
                    ->label('PP')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('accuracy')
                    ->label('Accuracy')
                    ->numeric()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => $state ? $state . "%" : null)
                    ->sortable(),
                TextColumn::make('learn_method')
                    ->label('Learn Method')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('level_learned_at')
                    ->label('Level')
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->level_learned_at > 0 ? $record->level_learned_at : null)
                    ->numeric()
                    ->placeholder('-'),
            ])

            ->defaultSort('name')
            ->paginated([10, 25, 50, 100]);
    }
}

<?php

namespace App\Livewire;

use App\Filament\Resources\Pokemon\PokemonResource;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MovePokemonTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public $move;

    public function mount($move): void
    {
        $this->move = $move;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Pokemon::query()
                    ->whereHas('moves', function ($query) {
                        $query->where('moves.id', $this->move->id);
                    })
                    ->with(['types' => function ($query) {
                        $query->orderBy('pokemon_type.slot');
                    }])
                    ->addSelect([
                        'learn_method' => \Illuminate\Support\Facades\DB::table('move_pokemon')
                            ->select('learn_method')
                            ->whereColumn('move_pokemon.pokemon_id', 'pokemon.id')
                            ->where('move_pokemon.move_id', $this->move->id)
                            ->limit(1),
                        'level_learned_at' => \Illuminate\Support\Facades\DB::table('move_pokemon')
                            ->select('level_learned_at')
                            ->whereColumn('move_pokemon.pokemon_id', 'pokemon.id')
                            ->where('move_pokemon.move_id', $this->move->id)
                            ->limit(1),
                    ])
            )
            ->columns([
                ImageColumn::make('sprite_front_default')
                    ->label('Sprite')
                    ->defaultImageUrl(asset('/images/3x-sprite-placeholder.png'))
                    ->size(56)
                    ->extraImgAttributes([
                        'class' => 'pixelated rounded-full',
                        'style' => 'image-rendering: pixelated; image-rendering: -moz-crisp-edges; image-rendering: crisp-edges;',
                    ])
                    ->url(fn($record) => PokemonResource::getUrl('view', ['record' => $record])),
                TextColumn::make('name')
                    ->label('Pokémon Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => ucwords(str_replace('-', ' ', $state)))
                    ->url(fn($record) => PokemonResource::getUrl('view', ['record' => $record])),
                TextColumn::make('types.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state): string => $state)
                    ->formatStateUsing(fn($state) => ucfirst($state)),
                TextColumn::make('learn_method')
                    ->label('Learn Method')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn($state) => ucwords(str_replace('-', ' ', $state ?? '-')))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('level_learned_at')
                    ->label('Level')
                    ->sortable()
                    ->numeric()
                    ->placeholder('-')
                    ->formatStateUsing(fn($state) => $state > 0 ? $state : null),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        return view('livewire.move-pokemon-table');
    }
}

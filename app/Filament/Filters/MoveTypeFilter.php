<?php

namespace App\Filament\Filters;

use App\Models\Type;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Filters\Filter;

class MoveTypeFilter
{
    /**
     * Create a reusable Move Type filter with configurable column layout
     *
     * Note: This is different from TypesFilter - it's for filtering moves by their single type,
     * not Pokemon by their multiple types.
     *
     * @param int|null $columns Number of columns (2, 3, or 4). Null for single column, 0 for grouped.
     * @param mixed $pokemon Optional Pokemon instance to filter available types
     * @return Filter
     */
    public static function make(?int $columns = null, $pokemon = null): Filter
    {
        return Filter::make('type')
            ->label('Move Type')
            ->schema([
                self::getToggleButtons($columns, $pokemon)
            ])
            ->query(function ($query, array $data) {
                if (!filled($data['type_ids'])) {
                    return;
                }

                // Moves have a single type, so we just filter by type_id
                $query->whereIn('type_id', $data['type_ids']);
            })
            ->indicateUsing(function (array $data): array {
                if (!filled($data['type_ids'])) {
                    return [];
                }

                $types = Type::whereIn('id', $data['type_ids'])->get();

                return [
                    \Filament\Tables\Filters\Indicator::make('Type: ' . $types->pluck('name')->map(fn($n) => ucfirst($n))->join(', '))
                        ->removeField('type_ids')
                ];
            });
    }

    /**
     * Get the ToggleButtons component with configured columns
     *
     * @param int|null $columns
     * @param mixed|null $pokemon
     * @return ToggleButtons
     */
    protected static function getToggleButtons(?int $columns, mixed $pokemon = null): ToggleButtons
    {
        $toggleButtons = ToggleButtons::make('type_ids')
            ->label('Type')
            ->options(function () use ($pokemon) {
                // If Pokemon is provided, only show types that exist in their move pool
                if ($pokemon) {
                    $typeIds = \Illuminate\Support\Facades\DB::table('moves')
                        ->join('move_pokemon', 'moves.id', '=', 'move_pokemon.move_id')
                        ->where('move_pokemon.pokemon_id', $pokemon->id)
                        ->whereNotNull('moves.type_id')
                        ->distinct()
                        ->pluck('moves.type_id');

                    return Type::whereIn('id', $typeIds)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->map(fn($name) => ucfirst($name))
                        ->toArray();
                }

                // Otherwise show all types
                return Type::orderBy('name')
                    ->pluck('name', 'id')
                    ->map(fn($name) => ucfirst($name))
                    ->toArray();
            })
            ->colors(function () use ($pokemon) {
                // If Pokemon is provided, only show colors for available types
                if ($pokemon) {
                    $typeIds = \Illuminate\Support\Facades\DB::table('moves')
                        ->join('move_pokemon', 'moves.id', '=', 'move_pokemon.move_id')
                        ->where('move_pokemon.pokemon_id', $pokemon->id)
                        ->whereNotNull('moves.type_id')
                        ->distinct()
                        ->pluck('moves.type_id');

                    return Type::whereIn('id', $typeIds)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray();
                }

                // Otherwise show all type colors
                return Type::orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->multiple();

        switch ($columns) {
            case 0:
                $toggleButtons = $toggleButtons->grouped();
                break;
            case 4:
            case 3:
            case 2:
                $toggleButtons = $toggleButtons->columns($columns);
                break;
            default:
            case 1:
                break;
        }

        return $toggleButtons;
    }
}

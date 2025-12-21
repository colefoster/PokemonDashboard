<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Filters\Filter;

class LearnMethodFilter
{
    /**
     * Create a reusable Learn Method filter
     *
     * @param int|null $columns Number of columns (2, 3, or 4). Null for single column, 0 for grouped.
     * @param mixed $pokemon Optional Pokemon instance to filter available learn methods
     * @return Filter
     */
    public static function make(?int $columns = null, $pokemon = null): Filter
    {
        return Filter::make('learn_method')
            ->label('Learn Method')
            ->schema([
                self::getToggleButtons($columns, $pokemon)
            ])
            ->query(function ($query, array $data) {
                if (!filled($data['learn_methods'])) {
                    return;
                }

                // Query the pivot table column directly since the relationship is already joined
                $query->whereIn('move_pokemon.learn_method', $data['learn_methods']);
            })
            ->indicateUsing(function (array $data): array {
                if (!filled($data['learn_methods'])) {
                    return [];
                }

                $methods = collect($data['learn_methods'])
                    ->map(fn($method) => ucwords(str_replace('-', ' ', $method)))
                    ->join(', ');

                return [
                    \Filament\Tables\Filters\Indicator::make('Learn Method: ' . $methods)
                        ->removeField('learn_methods')
                ];
            });
    }

    /**
     * Get the ToggleButtons component with configured columns
     *
     * @param int|null $columns
     * @param mixed $pokemon
     * @return ToggleButtons
     */
    protected static function getToggleButtons(?int $columns, $pokemon = null): ToggleButtons
    {
        // All possible learn method options with their labels
        $allMethods = [
            'level-up' => 'Level Up',
            'machine' => 'TM/HM',
            'egg' => 'Egg',
            'tutor' => 'Tutor',
            'stadium-surfing-pikachu' => 'Stadium',
            'light-ball-egg' => 'Light Ball',
            'colosseum-purification' => 'Purification',
            'xd-shadow' => 'XD Shadow',
            'xd-purification' => 'XD Purification',
            'form-change' => 'Form Change',
        ];

        $toggleButtons = ToggleButtons::make('learn_methods')
            ->label('Learn Method')
            ->options(function () use ($pokemon, $allMethods) {
                // If Pokemon is provided, only show learn methods that exist in their move pool
                if ($pokemon) {
                    $availableMethods = \Illuminate\Support\Facades\DB::table('move_pokemon')
                        ->where('pokemon_id', $pokemon->id)
                        ->whereNotNull('learn_method')
                        ->distinct()
                        ->pluck('learn_method')
                        ->toArray();

                    // Filter to only include available methods while preserving labels
                    return collect($allMethods)
                        ->filter(fn($label, $key) => in_array($key, $availableMethods))
                        ->toArray();
                }

                // Otherwise show all methods
                return $allMethods;
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
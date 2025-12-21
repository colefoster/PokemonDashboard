<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Filters\Filter;

class MoveClassFilter
{
    /**
     * Create a reusable Move Class (Damage Class) filter
     *
     * @param int|null $columns Number of columns (2, 3, or 4). Null for single column, 0 for grouped.
     * @return Filter
     */
    public static function make(?int $columns = null): Filter
    {
        return Filter::make('damage_class')
            ->label('Move Class')
            ->schema([
                self::getToggleButtons($columns)
            ])
            ->query(function ($query, array $data) {
                if (!filled($data['damage_classes'])) {
                    return;
                }

                $query->whereIn('damage_class', $data['damage_classes']);
            })
            ->indicateUsing(function (array $data): array {
                if (!filled($data['damage_classes'])) {
                    return [];
                }

                $classes = collect($data['damage_classes'])
                    ->map(fn($class) => ucfirst($class))
                    ->join(', ');

                return [
                    \Filament\Tables\Filters\Indicator::make('Class: ' . $classes)
                        ->removeField('damage_classes')
                ];
            });
    }

    /**
     * Get the ToggleButtons component with configured columns
     *
     * @param int|null $columns
     * @return ToggleButtons
     */
    protected static function getToggleButtons(?int $columns): ToggleButtons
    {
        $toggleButtons = ToggleButtons::make('damage_classes')
            ->label('Damage Class')
            ->options([
                'physical' => 'Physical',
                'special' => 'Special',
                'status' => 'Status',
            ])
            ->colors([
                'physical' => 'physical',
                'special' => 'special',
                'status' => 'status',
            ])
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
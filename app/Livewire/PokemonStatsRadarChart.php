<?php

namespace App\Livewire;

use App\Models\Pokemon;
use App\Providers\Filament\MyAppColors;
use Livewire\Component;

class PokemonStatsRadarChart extends Component
{
    public ?Pokemon $record = null;

    public function mount($record): void
    {
        $this->record = $record;
    }

    protected function getTypeColor(): string
    {
        if (!$this->record) {
            return '#3b82f6'; // Default blue
        }

        // Get the primary type (slot 1)
        $primaryType = $this->record->types()->wherePivot('slot', 1)->first();

        if (!$primaryType) {
            return '#3b82f6'; // Default blue
        }

        $colors = MyAppColors::loadAppColors();
        $typeName = strtolower($primaryType->name);

        return $colors[$typeName] ?? '#3b82f6';
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public function getChartData(): array
    {
        if (!$this->record) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $typeColor = $this->getTypeColor();
        $rgb = $this->hexToRgb($typeColor);
        $rgbString = "rgb({$rgb[0]}, {$rgb[1]}, {$rgb[2]})";
        $rgbaString = "rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, 0.2)";

        return [
            'datasets' => [
                [
                    'label' => $this->record->name,
                    'data' => [
                        $this->record->hpStat ?? 0,
                        $this->record->attackStat ?? 0,
                        $this->record->defenseStat ?? 0,
                        $this->record->specialAttackStat ?? 0,
                        $this->record->specialDefenseStat ?? 0,
                        $this->record->speedStat ?? 0,
                    ],
                    'backgroundColor' => $rgbaString, //Line fill color
                    'borderColor' => $rgbString,
                    'borderWidth' => 2,
                    'pointBackgroundColor' => $rgbString,
                    'pointBorderColor' => '#fff',
                    'pointHoverBackgroundColor' => '#fff',
                    'pointHoverBorderColor' => $rgbString,
                ],
            ],
            'labels' => ['HP', 'ATK', 'DEF', 'SPA', 'SPD', 'SPE'],
        ];
    }

    public function getChartOptions(): array
    {
        return [
            'scales' => [
                'r' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'max' => 200,
                    'ticks' => [
                        'stepSize' => 100,
                        'display' => false,
                    ],
                    'grid' => [
                        'color' => 'rgb(75, 85, 99)', // Circular grid lines
                        'lineWidth' => 1,
                    ],
                    'angleLines' => [
                        'color' => 'rgb(75, 85, 99)', // Lines radiating from center
                        'lineWidth' => 1,
                    ],
                    'pointLabels' => [
                        'color' => 'rgb(75, 85, 99)', // Axis labels (HP, ATK, etc.)
                        'font' => [
                            'size' => 12,
                            'weight' => 500,
                        ],
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }

    public function render()
    {
        return view('livewire.pokemon-stats-radar-chart');
    }
}
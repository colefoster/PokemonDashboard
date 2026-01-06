<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApiQueryWidget;
use App\Filament\Widgets\RunSeedersWidget;
use Filament\Pages\Page;

class AdminTools extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected string $view = 'filament.pages.admin-tools';

    protected static ?string $navigationLabel = 'Admin Tools';

    protected static ?string $title = 'Admin Tools';

    protected static ?int $navigationSort = 100;

    public string $activeTab = 'api-query';

    public static function canAccess(): bool
    {
        // Only admins can access this page
        return auth()->user()?->is_admin ?? false;
    }

    public function getTabs(): array
    {
        return [
            'api-query' => [
                'label' => 'API Tester',
                'icon' => 'heroicon-o-beaker',
            ],
            'data-importer' => [
                'label' => 'Data Importer',
                'icon' => 'heroicon-o-arrow-down-tray',
            ],
        ];
    }

    public function getVisibleWidgets(): array
    {
        return match($this->activeTab) {
            'data-importer' => [RunSeedersWidget::class],
            'api-query' => [ApiQueryWidget::class],
            default => [ApiQueryWidget::class],
        };
    }
}
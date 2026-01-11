<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TeambuilderPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Team Builder';

    protected static ?string $title = 'Team Builder';

    protected static ?int $navigationSort = 50;

    //protected static UnitEnum|string|null $navigationGroup = null; // Top-level navigation

    protected string $view = 'filament.pages.teambuilder-redirect';

    public function mount(): void
    {
        // Redirect to standalone Vue page
        $this->redirect(route('teambuilder'));
    }
}

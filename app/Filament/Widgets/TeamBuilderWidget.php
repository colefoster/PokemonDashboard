<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class TeamBuilderWidget extends Widget
{
    protected string $view = 'filament.widgets.team-builder-widget';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
}

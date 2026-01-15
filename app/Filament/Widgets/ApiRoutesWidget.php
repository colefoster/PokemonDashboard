<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Widgets\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

class ApiRoutesWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'filament.widgets.api-routes-widget';

    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];

    public ?string $responseBody = null;

    public ?int $statusCode = null;

    public ?string $statusText = null;

    public ?float $responseTime = null;

    public static function canView(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'route' => null,
        ]);
    }

    protected function getApiRoutes(): array
    {
        return [
            // Pokemon Database Routes
            'pokemon.index' => [
                'group' => 'Pokemon Database',
                'method' => 'GET',
                'path' => '/api/pokemon/',
                'name' => 'List Pokemon',
                'description' => 'Get a paginated list of all Pokemon with their types, stats, and species info',
                'params' => [
                    'page' => ['type' => 'number', 'default' => '1', 'label' => 'Page', 'description' => 'Page number'],
                    'per_page' => ['type' => 'number', 'default' => '15', 'label' => 'Per Page', 'description' => 'Items per page'],
                ],
            ],
            'pokemon.search' => [
                'group' => 'Pokemon Database',
                'method' => 'GET',
                'path' => '/api/pokemon/search',
                'name' => 'Search Pokemon',
                'description' => 'Search Pokemon by name (partial match, max 20 results)',
                'params' => [
                    'q' => ['type' => 'text', 'default' => 'pika', 'label' => 'Query', 'description' => 'Search query', 'required' => true],
                ],
            ],
            'pokemon.show' => [
                'group' => 'Pokemon Database',
                'method' => 'GET',
                'path' => '/api/pokemon/{apiId}',
                'name' => 'Get Pokemon',
                'description' => 'Get detailed info for a specific Pokemon by API ID',
                'params' => [
                    'apiId' => ['type' => 'number', 'default' => '25', 'label' => 'API ID', 'description' => 'Pokemon API ID (e.g., 25 for Pikachu)', 'required' => true, 'in_path' => true],
                ],
            ],

            // Format/Smogon Routes
            'formats.sets' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/sets',
                'name' => 'Get Format Sets',
                'description' => 'Get all Smogon sets for a competitive format (cached 1 hour)',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name (e.g., gen9ou)', 'required' => true, 'in_path' => true],
                ],
            ],
            'formats.sets.search' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/sets/search',
                'name' => 'Search Sets',
                'description' => 'Search Smogon sets by Pokemon name within a format',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name', 'required' => true, 'in_path' => true],
                    'q' => ['type' => 'text', 'default' => 'dragapult', 'label' => 'Query', 'description' => 'Pokemon name to search', 'required' => true],
                ],
            ],
            'formats.names' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/names',
                'name' => 'Get Format Names',
                'description' => 'Get array of all Pokemon names available in a format',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name', 'required' => true, 'in_path' => true],
                ],
            ],
            'formats.pokemon' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/pokemon',
                'name' => 'Get Format Pokemon',
                'description' => 'Get database data for all Pokemon in a format',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name', 'required' => true, 'in_path' => true],
                ],
            ],
            'formats.pokemon.search' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/pokemon/search',
                'name' => 'Search Format Pokemon',
                'description' => 'Search Pokemon in a format by name with database data',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name', 'required' => true, 'in_path' => true],
                    'q' => ['type' => 'text', 'default' => 'great', 'label' => 'Query', 'description' => 'Search query', 'required' => true],
                ],
            ],
            'formats.combined' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/combined',
                'name' => 'Get Combined Data',
                'description' => 'Get Smogon sets merged with database data for a format',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name', 'required' => true, 'in_path' => true],
                ],
            ],
            'formats.combined.search' => [
                'group' => 'Formats & Smogon',
                'method' => 'GET',
                'path' => '/api/formats/{format}/combined/search',
                'name' => 'Search Combined',
                'description' => 'Search combined Smogon + database data by Pokemon name',
                'params' => [
                    'format' => ['type' => 'text', 'default' => 'gen9ou', 'label' => 'Format', 'description' => 'Format name', 'required' => true, 'in_path' => true],
                    'q' => ['type' => 'text', 'default' => 'iron', 'label' => 'Query', 'description' => 'Search query', 'required' => true],
                ],
            ],

            // Sprite Routes
            'sprites.pokemon.styles' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/pokemon/styles',
                'name' => 'List Sprite Styles',
                'description' => 'Get available sprite styles (default, official-artwork, home, etc.)',
                'params' => [],
            ],
            'sprites.pokemon.generations' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/pokemon/generations',
                'name' => 'List Generations',
                'description' => 'Get available generation-specific sprite options',
                'params' => [],
            ],
            'sprites.pokemon.id' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/pokemon/{id}',
                'name' => 'Sprite by ID',
                'description' => 'Get sprite URL for a Pokemon by API ID',
                'params' => [
                    'id' => ['type' => 'number', 'default' => '25', 'label' => 'Pokemon ID', 'description' => 'Pokemon API ID', 'required' => true, 'in_path' => true],
                    'style' => ['type' => 'select', 'default' => 'default', 'label' => 'Style', 'description' => 'Sprite style', 'options' => ['default', 'official-artwork', 'home', 'dream-world', 'showdown']],
                    'shiny' => ['type' => 'boolean', 'default' => false, 'label' => 'Shiny', 'description' => 'Get shiny variant'],
                    'female' => ['type' => 'boolean', 'default' => false, 'label' => 'Female', 'description' => 'Get female variant'],
                ],
            ],
            'sprites.pokemon.name' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/pokemon/name/{name}',
                'name' => 'Sprite by Name',
                'description' => 'Get sprite URL for a Pokemon by name (supports fuzzy matching)',
                'params' => [
                    'name' => ['type' => 'text', 'default' => 'pikachu', 'label' => 'Name', 'description' => 'Pokemon name', 'required' => true, 'in_path' => true],
                    'style' => ['type' => 'select', 'default' => 'default', 'label' => 'Style', 'description' => 'Sprite style', 'options' => ['default', 'official-artwork', 'home', 'dream-world', 'showdown']],
                    'shiny' => ['type' => 'boolean', 'default' => false, 'label' => 'Shiny', 'description' => 'Get shiny variant'],
                ],
            ],
            'sprites.pokemon.batch' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/pokemon/batch',
                'name' => 'Batch Sprites',
                'description' => 'Get sprite URLs for multiple Pokemon by IDs',
                'params' => [
                    'ids' => ['type' => 'text', 'default' => '1,4,7,25', 'label' => 'IDs', 'description' => 'Comma-separated Pokemon IDs', 'required' => true],
                    'style' => ['type' => 'select', 'default' => 'default', 'label' => 'Style', 'description' => 'Sprite style', 'options' => ['default', 'official-artwork', 'home', 'dream-world', 'showdown']],
                ],
            ],
            'sprites.items' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/items/{name}',
                'name' => 'Item Sprite',
                'description' => 'Get sprite URL for an item by name',
                'params' => [
                    'name' => ['type' => 'text', 'default' => 'master-ball', 'label' => 'Item Name', 'description' => 'Item name (kebab-case)', 'required' => true, 'in_path' => true],
                ],
            ],
            'sprites.types' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/types/{name}',
                'name' => 'Type Sprite',
                'description' => 'Get sprite URL for a Pokemon type',
                'params' => [
                    'name' => ['type' => 'text', 'default' => 'electric', 'label' => 'Type Name', 'description' => 'Type name', 'required' => true, 'in_path' => true],
                ],
            ],
            'sprites.badges' => [
                'group' => 'Sprites',
                'method' => 'GET',
                'path' => '/api/sprites/badges/{name}',
                'name' => 'Badge Sprite',
                'description' => 'Get sprite URL for a gym badge',
                'params' => [
                    'name' => ['type' => 'text', 'default' => 'boulder-badge', 'label' => 'Badge Name', 'description' => 'Badge name (kebab-case)', 'required' => true, 'in_path' => true],
                ],
            ],
        ];
    }

    protected function getRouteOptions(): array
    {
        $options = [];
        $routes = $this->getApiRoutes();

        $groups = [];
        foreach ($routes as $key => $route) {
            $groups[$route['group']][$key] = "[{$route['method']}] {$route['name']}";
        }

        return $groups;
    }

    protected function getSelectedRoute(): ?array
    {
        $routeKey = $this->data['route'] ?? null;
        if (! $routeKey) {
            return null;
        }

        return $this->getApiRoutes()[$routeKey] ?? null;
    }

    protected function buildParamFields(): array
    {
        $route = $this->getSelectedRoute();
        if (! $route || empty($route['params'])) {
            return [];
        }

        $fields = [];
        foreach ($route['params'] as $paramKey => $param) {
            $field = match ($param['type']) {
                'select' => Select::make("params.{$paramKey}")
                    ->label($param['label'])
                    ->helperText($param['description'])
                    ->options(array_combine($param['options'], $param['options']))
                    ->default($param['default'])
                    ->live(),
                'boolean' => Toggle::make("params.{$paramKey}")
                    ->label($param['label'])
                    ->helperText($param['description'])
                    ->default($param['default'] ?? false)
                    ->live(),
                'number' => TextInput::make("params.{$paramKey}")
                    ->label($param['label'])
                    ->helperText($param['description'])
                    ->numeric()
                    ->default($param['default'] ?? '')
                    ->live(debounce: 500),
                default => TextInput::make("params.{$paramKey}")
                    ->label($param['label'])
                    ->helperText($param['description'])
                    ->default($param['default'] ?? '')
                    ->live(debounce: 500),
            };

            if (! empty($param['required'])) {
                $field = $field->required();
            }

            if (! empty($param['in_path'])) {
                $field = $field->hint('Path parameter');
            }

            $fields[] = $field;
        }

        return $fields;
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make(3)->schema([
                Section::make('Select Route')
                    ->icon('heroicon-o-queue-list')
                    ->description('Choose an API endpoint to test')
                    ->schema([
                        Select::make('route')
                            ->label('API Route')
                            ->options($this->getRouteOptions())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                // Reset params when route changes
                                $this->data['params'] = [];
                                $this->responseBody = null;
                                $this->statusCode = null;

                                // Set default params for selected route
                                $route = $this->getApiRoutes()[$state] ?? null;
                                if ($route) {
                                    foreach ($route['params'] as $paramKey => $param) {
                                        $this->data['params'][$paramKey] = $param['default'] ?? '';
                                    }
                                }
                            })
                            ->placeholder('Select an API route...'),
                    ])
                    ->columnSpan(1),

                Section::make('Route Details')
                    ->icon('heroicon-o-information-circle')
                    ->description(fn (Get $get) => $this->getApiRoutes()[$get('route')]['description'] ?? 'Select a route to see details')
                    ->schema(function (Get $get) {
                        $route = $this->getApiRoutes()[$get('route')] ?? null;
                        if (! $route) {
                            return [
                                Text::make('Select a route from the list to configure parameters and test the endpoint.')
                                    ->icon('heroicon-o-cursor-arrow-rays'),
                            ];
                        }

                        $paramFields = $this->buildParamFields();

                        return [
                            Placeholder::make('url_preview')
                                ->label('Request URL')
                                ->content(fn () => new HtmlString(
                                    '<div class="flex items-center gap-2" x-data="{ copied: false }">'.
                                    '<code class="flex-1 p-2 text-xs font-mono bg-gray-100 dark:bg-gray-800 rounded-lg break-all">'.
                                    e($this->buildUrl()).
                                    '</code>'.
                                    '<button type="button" class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-70 h-9 w-9 text-gray-400 hover:text-gray-500 focus-visible:ring-primary-600 dark:text-gray-500 dark:hover:text-gray-400 dark:focus-visible:ring-primary-500" '.
                                    'x-on:click="navigator.clipboard.writeText(\''.e($this->buildUrl()).'\'); copied = true; setTimeout(() => copied = false, 2000);" '.
                                    'x-tooltip="copied ? \'Copied!\' : \'Copy URL\'">'.
                                    '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>'.
                                    '</button></div>'
                                )),
                            ...(count($paramFields) > 0 ? [
                                Grid::make(2)->schema($paramFields),
                            ] : []),
                            Actions::make([
                                Action::make('execute')
                                    ->label('Execute Request')
                                    ->icon('heroicon-o-play')
                                    ->color('primary')
                                    ->action(fn () => $this->executeRequest()),
                                Action::make('clear')
                                    ->label('Clear')
                                    ->icon('heroicon-o-x-mark')
                                    ->color('gray')
                                    ->visible(fn () => $this->responseBody !== null)
                                    ->action(fn () => $this->clearResponse()),
                            ])->alignEnd(),
                        ];
                    })
                    ->columnSpan(2),
            ]),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function buildUrl(): string
    {
        $routeKey = $this->data['route'] ?? null;
        if (! $routeKey) {
            return '';
        }

        $route = $this->getApiRoutes()[$routeKey] ?? null;
        if (! $route) {
            return '';
        }

        $baseUrl = config('app.url');
        $path = $route['path'];
        $params = $this->data['params'] ?? [];

        // Replace path parameters
        $queryParams = [];
        foreach ($route['params'] as $paramKey => $param) {
            $value = $params[$paramKey] ?? $param['default'] ?? '';

            // Convert boolean values
            if ($param['type'] === 'boolean') {
                $value = $value ? 'true' : 'false';
            }

            if (! empty($param['in_path'])) {
                $path = str_replace('{'.$paramKey.'}', $value, $path);
            } elseif ($value !== '' && $value !== ($param['default'] ?? '')) {
                $queryParams[$paramKey] = $value;
            } elseif (! empty($param['required']) && $value !== '') {
                $queryParams[$paramKey] = $value;
            }
        }

        $url = $baseUrl.$path;
        if (! empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }

        return $url;
    }

    public function executeRequest(): void
    {
        $routeKey = $this->data['route'] ?? null;
        if (! $routeKey) {
            Notification::make()
                ->title('No Route Selected')
                ->warning()
                ->body('Please select an API route to test')
                ->send();

            return;
        }

        $route = $this->getApiRoutes()[$routeKey] ?? null;
        if (! $route) {
            return;
        }

        try {
            $startTime = microtime(true);

            // Build the path with parameters
            $path = $route['path'];
            $params = $this->data['params'] ?? [];
            $queryParams = [];

            foreach ($route['params'] as $paramKey => $param) {
                $value = $params[$paramKey] ?? $param['default'] ?? '';

                // Convert boolean values
                if ($param['type'] === 'boolean') {
                    $value = $value ? 'true' : 'false';
                }

                if (! empty($param['in_path'])) {
                    $path = str_replace('{'.$paramKey.'}', $value, $path);
                } elseif ($value !== '') {
                    $queryParams[$paramKey] = $value;
                }
            }

            // Create internal request and dispatch through the app
            $request = Request::create($path, 'GET', $queryParams);
            $response = app()->handle($request);

            $this->responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->statusCode = $response->getStatusCode();
            $this->statusText = $response->statusText();

            $content = $response->getContent();
            $body = json_decode($content, true);

            if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->responseBody = $content;
            } else {
                $this->responseBody = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            Notification::make()
                ->title('Request Complete')
                ->success()
                ->body("HTTP {$this->statusCode} in {$this->responseTime}ms")
                ->send();

        } catch (\Exception $e) {
            $this->statusCode = null;
            $this->statusText = 'Error';
            $this->responseBody = "Error: {$e->getMessage()}";
            $this->responseTime = null;

            Notification::make()
                ->title('Request Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function clearResponse(): void
    {
        $this->responseBody = null;
        $this->statusCode = null;
        $this->statusText = null;
        $this->responseTime = null;
    }
}

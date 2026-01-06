<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ApiQueryWidget extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected string $view = 'filament.widgets.api-query-widget';

    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];
    public ?string $responseBody = null;
    public ?int $statusCode = null;
    public ?string $statusText = null;
    public ?array $responseHeaders = null;
    public ?array $keyStructure = null;

    public static function canView(): bool
    {
        // Only show on Admin Tools page, not on dashboard
        return false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'method' => 'GET',
            'url' => 'https://api.sampleapis.com/futurama/characters',
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Generic API Tester')
                ->description('Test any API endpoint and view the response')
                ->icon('heroicon-o-beaker')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            Select::make('method')
                                ->label('HTTP Method')
                                ->options([
                                    'GET' => 'GET',
                                    'POST' => 'POST',
                                    'PUT' => 'PUT',
                                    'PATCH' => 'PATCH',
                                    'DELETE' => 'DELETE',
                                ])
                                ->default('GET')
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('url')
                                ->label('API URL')
                                ->placeholder('https://api.example.com/endpoint')
                                ->url()
                                ->required()
                                ->columnSpan(3),
                        ]),

                    Repeater::make('headers')
                        ->label('Headers')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('key')
                                        ->label('Header Name')
                                        ->placeholder('Content-Type')
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('value')
                                        ->label('Header Value')
                                        ->placeholder('application/json')
                                        ->required()
                                        ->columnSpan(1),
                                ]),
                        ])
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['key'] ?? null)
                        ->addActionLabel('Add Header')
                        ->defaultItems(0)
                        ->columnSpanFull(),

                    Textarea::make('body')
                        ->label('Request Body (JSON)')
                        ->placeholder('{"key": "value"}')
                        ->rows(5)
                        ->helperText('Optional: Enter request body as JSON (for POST, PUT, PATCH)')
                        ->visible(fn($get) => in_array($get('method'), ['POST', 'PUT', 'PATCH']))
                        ->columnSpanFull(),

                    Actions::make([
                        Action::make('query')
                            ->label('Execute Request')
                            ->icon('heroicon-o-play')
                            ->color('primary')
                            ->action(function () {
                                $this->executeQuery();
                            }),

                        Action::make('clear')
                            ->label('Clear Response')
                            ->icon('heroicon-o-x-mark')
                            ->color('gray')
                            ->visible(fn() => $this->responseBody !== null)
                            ->action(function () {
                                $this->responseBody = null;
                                $this->statusCode = null;
                                $this->statusText = null;
                                $this->responseHeaders = null;
                                $this->keyStructure = null;
                                Notification::make()
                                    ->title('Response Cleared')
                                    ->success()
                                    ->send();
                            }),

                        Action::make('downloadSqlite')
                            ->label('Download SQLite DB')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('success')
                            ->action(function () {
                                return $this->generateAndDownloadSqlite();
                            }),
                    ])
                    ->alignEnd(),
                ]),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    /**
     * Extract keys from nested array/object structure
     */
    protected function extractKeys($data, int $maxDepth = 2, int $currentDepth = 0): array
    {
        $keys = [];

        if (!is_array($data) || $currentDepth >= $maxDepth) {
            return $keys;
        }

        // If it's a list (numeric array), get keys from first item
        if (array_is_list($data)) {
            if (!empty($data) && is_array($data[0])) {
                return $this->extractKeys($data[0], $maxDepth, $currentDepth);
            }
            return $keys;
        }

        // Extract keys from associative array
        foreach ($data as $key => $value) {
            $keys[$key] = [];

            if (is_array($value)) {
                $subKeys = $this->extractKeys($value, $maxDepth, $currentDepth + 1);
                if (!empty($subKeys)) {
                    $keys[$key] = $subKeys;
                }
            }
        }

        return $keys;
    }

    public function executeQuery(): void
    {
        $data = $this->form->getState();

        try {
            // Parse headers if provided
            $headers = [];
            if (!empty($data['headers']) && is_array($data['headers'])) {
                foreach ($data['headers'] as $header) {
                    if (!empty($header['key']) && !empty($header['value'])) {
                        $headers[$header['key']] = $header['value'];
                    }
                }
            }

            // Parse body if provided
            $body = null;
            if (!empty($data['body']) && in_array($data['method'], ['POST', 'PUT', 'PATCH'])) {
                $bodyArray = json_decode($data['body'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $body = $bodyArray;
                } else {
                    throw new \Exception('Invalid JSON in request body');
                }
            }

            // Make the HTTP request
            $httpClient = Http::timeout(30)->withHeaders($headers);

            $response = match($data['method']) {
                'GET' => $httpClient->get($data['url']),
                'POST' => $httpClient->post($data['url'], $body ?? []),
                'PUT' => $httpClient->put($data['url'], $body ?? []),
                'PATCH' => $httpClient->patch($data['url'], $body ?? []),
                'DELETE' => $httpClient->delete($data['url']),
                default => throw new \Exception('Invalid HTTP method'),
            };

            // Store response data
            $this->statusCode = $response->status();
            $this->statusText = $response->reason();
            $this->responseHeaders = $response->headers();

            // Format the response body
            $body = $response->json();

            // If not JSON, use raw body
            if ($body === null) {
                $this->responseBody = $response->body();
                $this->keyStructure = null;
            } else {
                $this->responseBody = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                // Extract keys structure from JSON body
                if (is_array($body)) {
                    $this->keyStructure = $this->extractKeys($body);
                } else {
                    $this->keyStructure = null;
                }
            }

            Notification::make()
                ->title('Request Successful')
                ->success()
                ->body("HTTP {$response->status()} {$response->reason()} - {$data['method']} {$data['url']}")
                ->send();

        } catch (\Exception $e) {
            $this->statusCode = null;
            $this->statusText = 'Error';
            $this->responseBody = "Error: {$e->getMessage()}";

            Notification::make()
                ->title('Request Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function generateAndDownloadSqlite()
    {
        try {
            // Create a temporary file for the SQLite database
            $tempFile = tempnam(sys_get_temp_dir(), 'sample_db_') . '.sqlite';

            // Create SQLite database connection
            $pdo = new \PDO('sqlite:' . $tempFile);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create a simple table
            $pdo->exec('
                CREATE TABLE IF NOT EXISTS sample_data (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    value TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ');

            // Insert some sample data
            $stmt = $pdo->prepare('INSERT INTO sample_data (name, value) VALUES (?, ?)');
            $sampleData = [
                ['Example 1', 'Sample value 1'],
                ['Example 2', 'Sample value 2'],
                ['Example 3', 'Sample value 3'],
            ];

            foreach ($sampleData as $row) {
                $stmt->execute($row);
            }

            // Close the database connection
            $pdo = null;

            // Generate filename with timestamp
            $filename = 'sample_database_' . date('Y-m-d_His') . '.sqlite';

            // Create download response
            $response = Response::download($tempFile, $filename, [
                'Content-Type' => 'application/x-sqlite3',
            ])->deleteFileAfterSend(true);

            Notification::make()
                ->title('Database Generated')
                ->success()
                ->body('SQLite database has been generated and download started.')
                ->send();

            return $response;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Generation Failed')
                ->danger()
                ->body('Error generating SQLite database: ' . $e->getMessage())
                ->send();

            return null;
        }
    }
}

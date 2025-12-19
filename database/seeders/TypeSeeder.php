<?php

namespace Database\Seeders;

use App\Models\Type;
use App\Services\PokeApiService;
use App\Services\SeederProgressService;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    protected PokeApiService $api;
    protected SeederProgressService $progress;

    public function __construct()
    {
        $this->api = app(PokeApiService::class);
        $this->progress = app(SeederProgressService::class);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📋 Importing Types...');

        try {
            $response = $this->api->fetch('/type?limit=100&offset=0');
            $types = $response['results'] ?? [];

            $this->progress->start('types', count($types));

            $bar = $this->command->getOutput()->createProgressBar(count($types));
            $bar->start();

            foreach ($types as $typeData) {
                try {
                    $typeId = $this->api->extractIdFromUrl($typeData['url']);
                    $typeDetails = $this->api->fetch("/type/{$typeId}");

                    Type::updateOrCreate(
                        ['api_id' => $typeDetails['id']],
                        ['name' => $typeDetails['name']]
                    );

                    $this->progress->advance("Importing type: {$typeDetails['name']}");
                    $this->progress->success();

                    $bar->advance();
                    usleep(100000); // 100ms delay between requests
                } catch (\Exception $e) {
                    $this->command->warn("\nError importing type: " . $e->getMessage());
                    $this->progress->error($e->getMessage());
                }
            }

            $bar->finish();
            $this->command->newLine();
            $this->command->info("Types imported: " . Type::count());

            $this->progress->complete('types');
        } catch (\Exception $e) {
            $this->command->error('❌ Type import failed: ' . $e->getMessage());
            $this->progress->error($e->getMessage());
        }
    }
}

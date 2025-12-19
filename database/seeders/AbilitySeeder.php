<?php

namespace Database\Seeders;

use App\Models\Ability;
use App\Services\PokeApiService;
use App\Services\SeederProgressService;
use Illuminate\Database\Seeder;

class AbilitySeeder extends Seeder
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
        $this->command->info('⚡ Importing Abilities...');

        try {
            $offset = 0;
            $limit = 100;

            // Get total count first
            $initialResponse = $this->api->fetch("/ability?limit=1&offset=0");
            $totalCount = $initialResponse['count'] ?? 0;

            $this->progress->start('abilities', $totalCount);

            do {
                $response = $this->api->fetch("/ability?limit={$limit}&offset={$offset}");
                $abilities = $response['results'] ?? [];

                if (empty($abilities)) {
                    break;
                }

                $bar = $this->command->getOutput()->createProgressBar(count($abilities));
                $bar->start();

                foreach ($abilities as $abilityData) {
                    try {
                        $abilityId = $this->api->extractIdFromUrl($abilityData['url']);
                        $abilityDetails = $this->api->fetch("/ability/{$abilityId}");

                        $effectEntry = $this->api->getEnglishEffect($abilityDetails['effect_entries'] ?? []);

                        Ability::updateOrCreate(
                            ['api_id' => $abilityDetails['id']],
                            [
                                'name' => $abilityDetails['name'],
                                'effect' => $effectEntry['effect'] ?? null,
                                'short_effect' => $effectEntry['short_effect'] ?? null,
                                'is_main_series' => $abilityDetails['is_main_series'] ?? true,
                            ]
                        );

                        $this->progress->advance("Importing ability: {$abilityDetails['name']}");
                        $this->progress->success();

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing ability: " . $e->getMessage());
                        $this->progress->error($e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($abilities));

            $this->command->info("Abilities imported: " . Ability::count());
            $this->progress->complete('abilities');
        } catch (\Exception $e) {
            $this->command->error('❌ Ability import failed: ' . $e->getMessage());
            $this->progress->error($e->getMessage());
        }
    }
}

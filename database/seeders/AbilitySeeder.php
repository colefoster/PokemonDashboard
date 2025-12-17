<?php

namespace Database\Seeders;

use App\Models\Ability;
use App\Services\PokeApiService;
use Illuminate\Database\Seeder;

class AbilitySeeder extends Seeder
{
    protected PokeApiService $api;

    public function __construct()
    {
        $this->api = app(PokeApiService::class);
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

                        $bar->advance();
                        usleep(100000); // 100ms delay between requests
                    } catch (\Exception $e) {
                        $this->command->warn("\nError importing ability: " . $e->getMessage());
                    }
                }

                $bar->finish();
                $this->command->newLine();
                $offset += $limit;

            } while (!empty($abilities));

            $this->command->info("Abilities imported: " . Ability::count());
        } catch (\Exception $e) {
            $this->command->error('❌ Ability import failed: ' . $e->getMessage());
        }
    }
}

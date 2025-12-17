<?php

namespace App\Console\Commands\Seed;

use Illuminate\Console\Command;

class SeedAll extends Command
{
    protected $signature = 'seed:all
                            {--threads=1 : Number of parallel workers for each seeder}
                            {--delay=100 : Delay between requests in milliseconds}
                            {--limit=100 : Items per page}
                            {--skip-types : Skip types seeding}
                            {--skip-abilities : Skip abilities seeding}
                            {--skip-moves : Skip moves seeding}
                            {--skip-items : Skip items seeding}
                            {--skip-species : Skip pokemon species seeding}
                            {--skip-evolution-chains : Skip evolution chains seeding}
                            {--skip-pokemon : Skip pokemon seeding}';

    protected $description = 'Seed all Pokemon data from PokeAPI with parallel processing support';

    public function handle(): int
    {
        set_time_limit(0);

        $threads = (int) $this->option('threads');
        $delay = (int) $this->option('delay');
        $limit = (int) $this->option('limit');

        $this->info('🚀 Starting Pokemon database seeding from PokeAPI...');
        $this->info("Parallel workers per seeder: {$threads}");
        $this->info("Delay between requests: {$delay}ms");
        $this->newLine();

        $commands = [
            ['seed:types', 'Types', 'skip-types'],
            ['seed:abilities', 'Abilities', 'skip-abilities'],
            ['seed:moves', 'Moves', 'skip-moves'],
            ['seed:items', 'Items', 'skip-items'],
            ['seed:pokemon-species', 'Pokemon Species', 'skip-species'],
            ['seed:evolution-chains', 'Evolution Chains', 'skip-evolution-chains'],
            ['seed:pokemon', 'Pokemon', 'skip-pokemon'],
        ];

        $startTime = now();

        try {
            foreach ($commands as [$command, $label, $skipOption]) {
                if ($this->option($skipOption)) {
                    $this->warn("⏭️  Skipping {$label}...");
                    continue;
                }

                $this->info("📦 Seeding {$label}...");

                $options = [
                    '--threads' => $threads,
                    '--delay' => $delay,
                    '--limit' => $limit,
                ];

                $result = $this->call($command, $options);

                if ($result !== self::SUCCESS) {
                    $this->error("❌ Failed to seed {$label}");
                    return self::FAILURE;
                }

                $this->newLine();
            }

            $duration = now()->diffForHumans($startTime, true);

            $this->newLine();
            $this->info('✅ All seeding completed successfully!');
            $this->info("⏱️  Total time: {$duration}");
            $this->newLine();

            // Show summary
            $this->info('📊 Summary:');
            $this->table(
                ['Table', 'Count'],
                [
                    ['Types', \App\Models\Type::count()],
                    ['Abilities', \App\Models\Ability::count()],
                    ['Moves', \App\Models\Move::count()],
                    ['Items', \App\Models\Item::count()],
                    ['Pokemon Species', \App\Models\PokemonSpecies::count()],
                    ['Evolution Chains', \App\Models\EvolutionChain::count()],
                    ['Pokemon', \App\Models\Pokemon::count()],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Seeding failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

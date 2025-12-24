<?php

namespace App\Console\Commands\Seed;


use App\Models\User;
use Illuminate\Console\Command;

class SeedUsers extends Command
{
    protected $signature = 'seed:users
    {--no-guest?: Skip seeding guest user}
    {--no-admin?: Skip seeding admin user}';

    protected $description = 'Seed default users into the database';

    public function handle(): int
    {
        try {
            if (!$this->option('no-admin')) {
                $this->info('Seeding admin user...');
                if (User::where('email', 'test@example.com')->exists()) {
                    $this->info('Admin user already exists. Skipping...');
                } else {
                    User::firstOrCreate([
                        'name' => 'Administrator',
                        'email' => 'test@example.com',
                        'password' => 'password',
                        'is_admin' => true,
                    ]);
                }
            }

            if (!$this->option('no-guest')) {
                $this->info('Seeding guest user...');
                if (User::where('email', 'guest@example.com')->exists()) {
                    $this->info('Guest user already exists. Skipping...');
                } else {
                    User::firstOrCreate([
                        'name' => 'Guest',
                        'password' => 'password',
                        'email' => 'guest@example.com',
                        'is_admin' => true,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->error('Error seeding users: ' . $e->getMessage());
        }

        return 0;
    }
}

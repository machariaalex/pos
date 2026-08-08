<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SeedInitialUsers extends Command
{
    protected $signature = 'app:seed-initial-users';

    protected $description = 'Seed the demo users, but only on a genuinely empty database — never touches anything once any user exists, even if that user was renamed/re-emailed away from the seeded defaults';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('Users already exist — skipping (this only ever seeds a brand-new, empty database).');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => 'UserSeeder', '--force' => true]);

        return self::SUCCESS;
    }
}

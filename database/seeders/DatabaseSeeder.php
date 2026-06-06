<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);

        // The demo dataset that powers the citizen-facing API — never in production.
        if (! app()->environment('production')) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}

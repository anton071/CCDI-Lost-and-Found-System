<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/*
 * Seeds reference data only. No user accounts are created here on purpose: shipping a
 * seeded administrator with a known password would be a security hole.
 * Create the first administrator with:  php artisan ccdi:make-admin you@example.com
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);
    }
}

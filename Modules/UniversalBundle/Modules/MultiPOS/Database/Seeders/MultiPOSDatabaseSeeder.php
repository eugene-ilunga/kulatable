<?php

namespace Modules\MultiPOS\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class MultiPOSDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This is the main seeder for the MultiPOS module.
     * It will call all module-specific seeders.
     */
    public function run(): void
    {
        // Only run if not in codecanyon environment
        if (app()->environment('codecanyon')) {
            $this->command->info('Skipping MultiPOS seeders: Running in codecanyon environment');
            return;
        }

        $this->command->info('Running MultiPOS module seeders...');

        // Seed POS machines
        $this->call(PosMachineSeeder::class);

        $this->command->info('MultiPOS module seeding completed!');
    }
}

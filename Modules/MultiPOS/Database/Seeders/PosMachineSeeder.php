<?php

namespace Modules\MultiPOS\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\MultiPOS\Entities\PosMachine;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PosMachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates 3 POS machines for each restaurant and assigns random
     * pos_machine_id to existing orders.
     *
     * Only runs when environment is NOT codecanyon.
     */
    public function run(): void
    {
        // Only run if not in codecanyon environment
        if (app()->environment('codecanyon')) {
            $this->command->info('Skipping PosMachineSeeder: Running in codecanyon environment');
            return;
        }

        $this->command->info('Seeding POS machines for all restaurants...');

        $restaurants = Restaurant::with('branches')->get();

        foreach ($restaurants as $restaurant) {
            $this->command->info("Seeding POS machines for restaurant ID: {$restaurant->id}");

            // Get all branches for this restaurant
            $branches = $restaurant->branches;

            if ($branches->isEmpty()) {
                $this->command->warn("  No branches found for restaurant ID: {$restaurant->id}, skipping...");
                continue;
            }

            // Create 3 POS machines for each branch
            foreach ($branches as $branch) {
                $this->command->info("  Creating 3 POS machines for branch: {$branch->name} (ID: {$branch->id})");

                for ($i = 1; $i <= 3; $i++) {
                    // Generate unique device_id (64 characters, same as in ClaimMachineController)
                    $deviceId = Str::random(64);

                    // Create POS machine
                    $machine = PosMachine::create([
                        'branch_id' => $branch->id,
                        'alias' => "{$branch->name} POS {$i}",
                        'public_id' => (string) Str::ulid(),
                        'token' => Str::random(64), // Unique token for each machine
                        'device_id' => $deviceId, // Random device_id for seeding
                        'status' => 'active', // Set as active so they're ready to use
                        'created_by' => null, // No specific user for seeded machines
                        'approved_by' => null,
                        'approved_at' => now(), // Auto-approved for seeded machines
                        'last_seen_at' => now()->subDays(rand(0, 30)), // Random last seen date
                    ]);

                    $this->command->info("    Created POS machine: {$machine->alias} (ID: {$machine->id}, Public ID: {$machine->public_id})");
                }
            }
        }

        $this->command->info('Assigning random pos_machine_id to existing orders...');

        // Get all orders that don't have a pos_machine_id
        $ordersWithoutMachine = Order::whereNull('pos_machine_id')
            ->whereNotNull('branch_id')
            ->get();

        if ($ordersWithoutMachine->isEmpty()) {
            $this->command->info('  No orders found without pos_machine_id');
        } else {
            $this->command->info("  Found {$ordersWithoutMachine->count()} orders without pos_machine_id");

            // Get all active POS machines grouped by branch
            $machinesByBranch = PosMachine::where('status', 'active')
                ->get()
                ->groupBy('branch_id');

            $updatedCount = 0;

            foreach ($ordersWithoutMachine as $order) {
                // Get machines for this order's branch
                $branchMachines = $machinesByBranch->get($order->branch_id);

                if ($branchMachines && $branchMachines->isNotEmpty()) {
                    // Randomly select a machine from this branch
                    $randomMachine = $branchMachines->random();

                    $order->pos_machine_id = $randomMachine->id;
                    $order->save();

                    $updatedCount++;
                }
            }

            $this->command->info("  Updated {$updatedCount} orders with random pos_machine_id");
        }

        $this->command->info('POS machine seeding completed!');
    }
}

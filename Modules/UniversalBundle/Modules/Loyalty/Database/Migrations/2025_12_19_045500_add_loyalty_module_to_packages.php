<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Module;
use App\Models\Package;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if modules table has any records (to avoid errors on fresh install)
        $checkModule = Module::count();
        $checkLoyaltyModule = Module::where('name', 'Loyalty')->first();

        if ($checkModule > 0 && !$checkLoyaltyModule) {
            // Create Loyalty module
            $loyaltyModule = Module::create(['name' => 'Loyalty']);

            // Add Loyalty module to all existing packages
            $packages = Package::all();
            
            foreach ($packages as $package) {
                // Check if module is already attached to avoid duplicates
                $exists = DB::table('package_modules')
                    ->where('package_id', $package->id)
                    ->where('module_id', $loyaltyModule->id)
                    ->exists();

                if (!$exists) {
                    DB::table('package_modules')->insert([
                        'package_id' => $package->id,
                        'module_id' => $loyaltyModule->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $loyaltyModule = Module::where('name', 'Loyalty')->first();
        
        if ($loyaltyModule) {
            // Remove from all packages
            DB::table('package_modules')
                ->where('module_id', $loyaltyModule->id)
                ->delete();

            // Delete the module
            $loyaltyModule->delete();
        }
    }
};


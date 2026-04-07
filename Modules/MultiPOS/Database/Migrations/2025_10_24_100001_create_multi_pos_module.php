<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\Package;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Restaurant;
use Modules\MultiPOS\Entities\MultiPOSGlobalSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $multiPOSModule = Module::firstOrCreate(['name' => MultiPOSGlobalSetting::MODULE_NAME]);

        $permissionName = 'Manage MultiPOS Machines';


        $permission = Permission::firstOrCreate([
            'guard_name' => 'web',
            'name' => $permissionName,
            'module_id' => $multiPOSModule->id,
        ]);

        Restaurant::chunk(50, function ($restaurants) use ($permission) {
            foreach ($restaurants as $restaurant) {
                $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
                $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();
                if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
                if ($branchHeadRole && !$branchHeadRole->hasPermissionTo($permission)) {
                    $branchHeadRole->givePermissionTo($permission);
                }
            }
        });


        // Add MultiPOS module to all existing packages
        $packages = Package::all();
        foreach ($packages as $package) {
            // Add MultiPOS to package modules if not already attached
            if (!$package->modules()->where('modules.id', $multiPOSModule->id)->exists()) {
                $package->modules()->attach($multiPOSModule->id);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};

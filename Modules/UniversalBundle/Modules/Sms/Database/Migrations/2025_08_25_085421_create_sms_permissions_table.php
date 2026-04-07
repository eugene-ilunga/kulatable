<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\Restaurant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $smsModule = Module::firstOrCreate(['name' => 'Sms']);

        $permissionName = 'Update Sms Setting';

        // Create the permission if it doesn't exist
        $permission = Permission::firstOrCreate(
            [
                'guard_name' => 'web',
                'name' => $permissionName,
            ],
            [
                'module_id' => $smsModule->id,
            ]
        );

        // Process restaurants in chunks to avoid memory and performance issues
        Restaurant::chunk(100, function ($restaurants) use ($permission) {
            foreach ($restaurants as $restaurant) {
                // Get roles for this specific restaurant
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $smsModule = Module::where('name', 'Sms')->first();

        if (!$smsModule) {
            return;
        }

        $permissions = Permission::where('module_id', $smsModule->id)
            ->where('guard_name', 'web')
            ->get();

        if ($permissions->isEmpty()) {
            return;
        }

        $permissionNames = $permissions->pluck('name')->toArray();

        // Process restaurants in chunks to avoid memory and performance issues
        Restaurant::chunk(100, function ($restaurants) use ($permissionNames) {
            foreach ($restaurants as $restaurant) {
                // Get roles for this specific restaurant
                $adminRole = Role::where('name', 'Admin_' . $restaurant->id)->first();
                $branchHeadRole = Role::where('name', 'Branch Head_' . $restaurant->id)->first();

                if ($adminRole) {
                    $adminRole->revokePermissionTo($permissionNames);
                }

                if ($branchHeadRole) {
                    $branchHeadRole->revokePermissionTo($permissionNames);
                }
            }
        });

        // Delete the permissions
        $permissions->each->delete();
    }
};

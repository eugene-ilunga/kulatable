<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Module;
use App\Models\Package;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure module row exists
        $module = Module::firstOrCreate(['name' => 'Aitools']);

        // Ensure permissions exist
        $perms = [
            'Manage AI Settings',
            'Access AI Assistant',
        ];

        foreach ($perms as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web'],
                ['module_id' => $module->id]
            );
        }

        // Attach module to all packages so restaurant_modules() includes it
        $packageIds = Package::pluck('id')->all();
        if (! empty($packageIds)) {
            $module->packages()->syncWithoutDetaching($packageIds);
        }

        // Grant permissions to admin roles (name starts with Admin_ or display_name Admin)
        $adminRoles = Role::query()
            ->where(function ($q) {
                $q->where('name', 'like', 'Admin_%')
                    ->orWhere('display_name', 'Admin');
            })
            ->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($perms);
        }
    }

    public function down(): void
    {
        // Do not detach on down to avoid removing live data
    }
};

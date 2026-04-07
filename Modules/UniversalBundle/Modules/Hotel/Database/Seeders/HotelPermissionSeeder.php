<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Module;
use App\Models\Restaurant;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class HotelPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create Hotel module
        $hotelModule = Module::firstOrCreate(['name' => 'Hotel']);

        $permissions = [
            // Front Desk
            ['guard_name' => 'web', 'name' => 'Show Hotel Front Desk', 'module_id' => $hotelModule->id],
            
            // Rooms
            ['guard_name' => 'web', 'name' => 'Create Hotel Room', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Show Hotel Rooms', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Room', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Room', 'module_id' => $hotelModule->id],
            
            // Room Types
            ['guard_name' => 'web', 'name' => 'Create Hotel Room Type', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Show Hotel Room Types', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Room Type', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Room Type', 'module_id' => $hotelModule->id],
            
            // Reservations
            ['guard_name' => 'web', 'name' => 'Create Hotel Reservation', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Show Hotel Reservations', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Reservation', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Reservation', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Cancel Hotel Reservation', 'module_id' => $hotelModule->id],
            
            // Check-in/Check-out
            ['guard_name' => 'web', 'name' => 'Check In Hotel Guest', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Check Out Hotel Guest', 'module_id' => $hotelModule->id],
            
            // Folios
            ['guard_name' => 'web', 'name' => 'Show Hotel Folio', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Post To Hotel Folio', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Apply Hotel Folio Discount', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Apply Hotel Folio Adjustment', 'module_id' => $hotelModule->id],
            
            // Guests
            ['guard_name' => 'web', 'name' => 'Create Hotel Guest', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Show Hotel Guests', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Guest', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Guest', 'module_id' => $hotelModule->id],
            
            // Rate Plans
            ['guard_name' => 'web', 'name' => 'Create Hotel Rate Plan', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Show Hotel Rate Plans', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Rate Plan', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Rate Plan', 'module_id' => $hotelModule->id],
            
            // Housekeeping
            ['guard_name' => 'web', 'name' => 'Create Hotel Housekeeping Task', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Show Hotel Housekeeping', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Housekeeping Task', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Complete Hotel Housekeeping Task', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Housekeeping Task', 'module_id' => $hotelModule->id],
            
            // Room Service
            ['guard_name' => 'web', 'name' => 'Show Hotel Room Service', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Create Hotel Room Service Order', 'module_id' => $hotelModule->id],
            
            // Banquet
            ['guard_name' => 'web', 'name' => 'Show Hotel Banquet', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Create Hotel Event', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Event', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Event', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Create Hotel Venue', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Update Hotel Venue', 'module_id' => $hotelModule->id],
            ['guard_name' => 'web', 'name' => 'Delete Hotel Venue', 'module_id' => $hotelModule->id],

            // Stays
            ['guard_name' => 'web', 'name' => 'Show Hotel Stays', 'module_id' => $hotelModule->id],
        ];

        // Insert permissions (skip if already exists)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                $permission
            );
        }

        // Get all Hotel module permissions
        $hotelPermissions = Permission::where('module_id', $hotelModule->id)->get()->pluck('name')->toArray();

        // Assign permissions to Admin and Branch Head roles for all restaurants
        $restaurantIds = Restaurant::pluck('id');
        
        if ($restaurantIds->isEmpty()) {
            return;
        }

        // Build role names for bulk query
        $roleNames = $restaurantIds->flatMap(function ($id) {
            return ["Admin_{$id}", "Branch Head_{$id}"];
        })->toArray();

        // Fetch all roles in a single query and assign permissions
        Role::whereIn('name', $roleNames)->get()->each(function ($role) use ($hotelPermissions) {
            $role->givePermissionTo($hotelPermissions);
        });
    }
}

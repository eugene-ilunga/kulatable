<?php

use App\Models\Branch;
use App\Models\OrderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Room Service order type for all branches
        $branches = Branch::all();

        foreach ($branches as $branch) {
            // Check if room service order type already exists
            $exists = OrderType::where('branch_id', $branch->id)
                ->where('slug', 'room_service')
                ->exists();

            if (!$exists) {
                DB::table('order_types')->insert([
                    'branch_id' => $branch->id,
                    'order_type_name' => 'Room Service',
                    'slug' => 'room_service',
                    'is_active' => true,
                    'is_default' => false,
                    'type' => 'room_service',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Room Service order type
        OrderType::where('slug','room_service')->delete();
    }
}; 
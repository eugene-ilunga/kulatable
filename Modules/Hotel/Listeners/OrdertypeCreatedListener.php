<?php

namespace Modules\Hotel\Listeners;

use App\Events\NewBranchCreatedEvent;
use App\Models\OrderType;
use Illuminate\Support\Facades\DB;

class OrdertypeCreatedListener
{
    public function handle(NewBranchCreatedEvent $event): void
    {
        $branch = $event->branch;

        // Only create room service order type if Hotel module is enabled
        if (!module_enabled('Hotel')) {
            return;
        }

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

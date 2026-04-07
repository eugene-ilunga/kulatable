<?php

namespace Modules\Hotel\Console;

use App\Models\Branch;
use App\Models\OrderType;
use App\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ActivateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'hotel:activate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all the module settings of hotel module';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
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

        $this->info('Hotel Module activated successfully!');
    }
}

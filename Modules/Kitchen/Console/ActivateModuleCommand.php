<?php

namespace Modules\Kitchen\Console;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Console\Command;

class ActivateModuleCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'kitchen:activate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all the module settings of kitchen module';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $restaurant = Restaurant::with('branches')->get();

        foreach ($restaurant as $restaurant) {

            foreach ($restaurant->branches as $branch) {

                $kotPlace = $branch->kotPlaces()->first();

                if ($kotPlace) {
                    MenuItem::whereNull('kot_place_id')
                        ->whereNull('kot_place_id')
                        ->update(['kot_place_id' => $kotPlace->id]);
                }
            }
        }
    }
}

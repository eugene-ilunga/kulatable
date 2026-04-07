<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\Tax;

class HotelTaxSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                $exists = Tax::where('restaurant_id', $restaurant->id)
                    ->where('branch_id', $branch->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Tax::create([
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $branch->id,
                    'name' => 'VAT 5%',
                    'rate' => 5,
                    'is_active' => true,
                ]);

                Tax::create([
                    'restaurant_id' => $restaurant->id,
                    'branch_id' => $branch->id,
                    'name' => 'Service Tax 10%',
                    'rate' => 10,
                    'is_active' => true,
                ]);
            }
        }
    }
}

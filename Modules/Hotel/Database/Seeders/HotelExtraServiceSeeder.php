<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\ExtraService;

class HotelExtraServiceSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        $extras = [
            ['name' => 'Breakfast', 'price' => 15.00],
            ['name' => 'Parking', 'price' => 10.00],
            ['name' => 'Airport Transfer', 'price' => 25.00],
            ['name' => 'Laundry', 'price' => 8.00],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                $exists = ExtraService::where('restaurant_id', $restaurant->id)
                    ->where('branch_id', $branch->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                foreach ($extras as $extra) {
                    ExtraService::create([
                        'restaurant_id' => $restaurant->id,
                        'branch_id' => $branch->id,
                        'name' => $extra['name'],
                        'price' => $extra['price'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}

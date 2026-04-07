<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\RoomType;

class HotelRoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        $roomTypes = [
            [
                'name' => 'Standard Room',
                'image' => null,
                'description' => 'Comfortable standard room with essential amenities',
                'max_occupancy' => 2,
                'base_occupancy' => 2,
                'amenities' => ['WiFi', 'TV', 'AC'],
                'base_rate' => 2000.00,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Deluxe Room',
                'image' => null,
                'description' => 'Spacious deluxe room with premium amenities',
                'max_occupancy' => 3,
                'base_occupancy' => 2,
                'amenities' => ['WiFi', 'TV', 'AC', 'Mini Bar', 'Balcony'],
                'base_rate' => 3500.00,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Suite',
                'image' => null,
                'description' => 'Luxurious suite with separate living area',
                'max_occupancy' => 4,
                'base_occupancy' => 2,
                'amenities' => ['WiFi', 'TV', 'AC', 'Mini Bar', 'Balcony', 'Living Room', 'Kitchenette'],
                'base_rate' => 5500.00,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                foreach ($roomTypes as $roomTypeData) {
                    // Check if room type already exists for this restaurant and branch
                    $exists = RoomType::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('name', $roomTypeData['name'])
                        ->exists();

                    if (!$exists) {
                        RoomType::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'name' => $roomTypeData['name'],
                            'image' => $roomTypeData['image'] ?? null,
                            'description' => $roomTypeData['description'],
                            'max_occupancy' => $roomTypeData['max_occupancy'],
                            'base_occupancy' => $roomTypeData['base_occupancy'],
                            'amenities' => $roomTypeData['amenities'],
                            'base_rate' => $roomTypeData['base_rate'],
                            'is_active' => $roomTypeData['is_active'],
                            'sort_order' => $roomTypeData['sort_order'],
                        ]);
                    }
                }
            }
        }
    }
}


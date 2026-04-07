<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\Guest;

class HotelGuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        $guests = [
            [
                'first_name' => 'John',
                'phone' => '9876543210',
                'id_type' => 'national_id',
                'id_number' => 'ID001',
            ],
            [
                'first_name' => 'Sarah',
                'phone' => '9876543211',
                'id_type' => 'passport',
                'id_number' => 'PASS001',
            ],
            [
                'first_name' => 'Michael',
                'phone' => '9876543212',
                'id_type' => 'driving_license',
                'id_number' => 'DL001',
            ],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                foreach ($guests as $guestData) {
                    // Check if guest already exists for this restaurant and branch
                    $exists = Guest::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('phone', $guestData['phone'])
                        ->exists();

                    if (!$exists) {
                        Guest::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'first_name' => $guestData['first_name'],
                            'phone' => $guestData['phone'],
                            'id_type' => $guestData['id_type'],
                            'id_number' => $guestData['id_number'],
                        ]);
                    }
                }
            }
        }
    }
}


<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Enums\RoomStatus;

class HotelRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        // Room type names in order (matching the seeder)
        $roomTypeNames = ['Standard Room', 'Deluxe Room', 'Suite'];

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                // Get room types for this restaurant and branch
                $roomTypes = RoomType::where('restaurant_id', $restaurant->id)
                    ->where('branch_id', $branch->id)
                    ->whereIn('name', $roomTypeNames)
                    ->orderBy('sort_order')
                    ->get();

                // Only create rooms if room types exist
                if ($roomTypes->isEmpty()) {
                    continue;
                }

                // Create 3 rooms, one for each room type
                $baseRoomNumber = 101; // Start from 101
                foreach ($roomTypes->take(3) as $index => $roomType) {
                    // Generate unique room number (using branch ID as prefix to ensure global uniqueness)
                    $roomNumber = $branch->id . str_pad($baseRoomNumber + $index, 3, '0', STR_PAD_LEFT);
                    
                    // Ensure global uniqueness
                    $uniqueRoomNumber = $this->generateUniqueRoomNumber($roomNumber);

                    // Check if room already exists for this restaurant, branch, and room type
                    $exists = Room::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('room_type_id', $roomType->id)
                        ->exists();

                    if (!$exists) {
                        Room::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'room_type_id' => $roomType->id,
                            'room_number' => $uniqueRoomNumber,
                            'floor' => '1',
                            'status' => RoomStatus::VACANT_CLEAN->value,
                            'notes' => null,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Generate a globally unique room number
     */
    private function generateUniqueRoomNumber(string $baseRoomNumber): string
    {
        $roomNumber = $baseRoomNumber;
        $counter = 0;
        $maxAttempts = 1000;

        while (
            Room::where('room_number', $roomNumber)->exists() && $counter < $maxAttempts
        ) {
            $counter++;
            // Append counter to make it unique
            $roomNumber = $baseRoomNumber . '-' . $counter;
        }

        return $roomNumber;
    }
}


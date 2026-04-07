<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Guest;
use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Enums\ReservationStatus;
use Carbon\Carbon;

class HotelReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                // Get guests for this branch
                $guests = Guest::where('restaurant_id', $restaurant->id)
                    ->where('branch_id', $branch->id)
                    ->limit(3)
                    ->get();

                // Get rate plans for this branch (optional)
                $ratePlans = RatePlan::where('restaurant_id', $restaurant->id)
                    ->where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->get();

                if ($guests->isEmpty()) {
                    continue;
                }

                // Create 3 reservations
                $reservationCounter = 1;
                foreach ($guests->take(3) as $index => $guest) {
                    $reservationNumber = $this->generateUniqueReservationNumber($restaurant->id, $branch->id, $reservationCounter);

                    // Check if reservation already exists
                    $exists = Reservation::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('primary_guest_id', $guest->id)
                        ->exists();

                    if (!$exists) {
                        // Set check-in date to today
                        $checkInDate = Carbon::today();
                        // Set check-out date to tomorrow
                        $checkOutDate = Carbon::tomorrow();

                        // Get a rate plan if available (optional)
                        $ratePlanId = $ratePlans->isNotEmpty() ? $ratePlans->random()->id : null;

                        Reservation::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'reservation_number' => $reservationNumber,
                            'primary_guest_id' => $guest->id,
                            'check_in_date' => $checkInDate->format('Y-m-d'),
                            'check_out_date' => $checkOutDate->format('Y-m-d'),
                            'check_in_time' => '14:00:00',
                            'check_out_time' => '11:00:00',
                            'rooms_count' => 1,
                            'adults' => 1 + $index, // 1, 2, or 3 adults
                            'children' => $index > 1 ? 1 : 0, // 1 child for 3rd reservation
                            'rate_plan_id' => $ratePlanId,
                            'status' => ReservationStatus::TENTATIVE->value,
                        ]);
                    }

                    $reservationCounter++;
                }
            }
        }
    }

    /**
     * Generate a unique reservation number
     */
    private function generateUniqueReservationNumber(int $restaurantId, int $branchId, int $counter): string
    {
        $baseNumber = 'RES-' . $branchId . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
        $reservationNumber = $baseNumber;
        $maxAttempts = 1000;
        $attempt = 0;

        while (
            Reservation::where('reservation_number', $reservationNumber)->exists() && $attempt < $maxAttempts
        ) {
            $attempt++;
            $reservationNumber = $baseNumber . '-' . $attempt;
        }

        return $reservationNumber;
    }
}


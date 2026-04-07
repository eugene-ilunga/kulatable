<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Entities\Rate;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Enums\RatePlanType;
use Carbon\Carbon;

class HotelRatePlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        // Define 3 rate plans
        $ratePlans = [
            [
                'name' => 'Standard Plan',
                'description' => 'Standard rate plan with flexible cancellation',
                'type' => RatePlanType::EP->value,
                'cancellation_hours' => 24,
                'cancellation_charge_percent' => 10.00,
            ],
            [
                'name' => 'Premium Plan',
                'description' => 'Premium rate plan with breakfast included',
                'type' => RatePlanType::CP->value,
                'cancellation_hours' => 48,
                'cancellation_charge_percent' => 5.00,
            ],
            [
                'name' => 'All Inclusive Plan',
                'description' => 'All inclusive plan with all meals',
                'type' => RatePlanType::AP->value,
                'cancellation_hours' => 72,
                'cancellation_charge_percent' => 0.00,
            ],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                // Get room types for this restaurant and branch
                $roomTypes = RoomType::where('restaurant_id', $restaurant->id)
                    ->where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->get();

                if ($roomTypes->isEmpty()) {
                    continue;
                }

                // Create rate plans
                foreach ($ratePlans as $ratePlanData) {
                    // Check if rate plan already exists
                    $ratePlan = RatePlan::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('name', $ratePlanData['name'])
                        ->first();

                    if (!$ratePlan) {
                        $ratePlan = RatePlan::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'name' => $ratePlanData['name'],
                            'description' => $ratePlanData['description'],
                            'type' => $ratePlanData['type'],
                            'cancellation_hours' => $ratePlanData['cancellation_hours'],
                            'cancellation_charge_percent' => $ratePlanData['cancellation_charge_percent'],
                            'is_active' => true,
                        ]);
                    }

                    // Create rates for each room type
                    foreach ($roomTypes as $roomType) {
                        // Check if rate already exists
                        $rateExists = Rate::where('restaurant_id', $restaurant->id)
                            ->where('branch_id', $branch->id)
                            ->where('room_type_id', $roomType->id)
                            ->where('rate_plan_id', $ratePlan->id)
                            ->exists();

                        if (!$rateExists) {
                            // Calculate rates based on room type base rate and plan type
                            $baseRate = $roomType->base_rate;
                            $rateMultiplier = match($ratePlanData['type']) {
                                RatePlanType::EP->value => 1.0,      // Room only
                                RatePlanType::CP->value => 1.15,    // Room + Breakfast (15% more)
                                RatePlanType::MAP->value => 1.30,    // Room + Breakfast + Dinner (30% more)
                                RatePlanType::AP->value => 1.50,     // Room + All Meals (50% more)
                                default => 1.0,
                            };

                            $calculatedRate = $baseRate * $rateMultiplier;

                            Rate::create([
                                'restaurant_id' => $restaurant->id,
                                'branch_id' => $branch->id,
                                'room_type_id' => $roomType->id,
                                'rate_plan_id' => $ratePlan->id,
                                'date_from' => Carbon::now()->startOfYear(),
                                'date_to' => Carbon::now()->endOfYear()->addYear(),
                                'rate' => $calculatedRate,
                                'single_occupancy_rate' => $calculatedRate * 0.8,
                                'double_occupancy_rate' => $calculatedRate,
                                'extra_person_rate' => $calculatedRate * 0.3,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }
        }
    }
}


<?php

namespace Modules\Hotel\Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Modules\Hotel\Entities\Venue;
use Modules\Hotel\Entities\Event;
use Modules\Hotel\Entities\EventCharge;
use Modules\Hotel\Enums\EventStatus;
use Modules\Hotel\Enums\FolioLineType;
use Carbon\Carbon;

class HotelVenueEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::with('branches')->get();

        // Define 3 venues
        $venues = [
            [
                'name' => 'Grand Ballroom',
                'description' => 'Spacious ballroom perfect for large events and celebrations',
                'capacity' => 200,
                'base_rate' => 50000.00,
                'amenities' => ['Stage', 'Sound System', 'Lighting', 'Projector', 'Catering'],
            ],
            [
                'name' => 'Conference Hall',
                'description' => 'Professional conference hall for business meetings and seminars',
                'capacity' => 100,
                'base_rate' => 30000.00,
                'amenities' => ['Projector', 'Whiteboard', 'WiFi', 'Catering'],
            ],
            [
                'name' => 'Garden Pavilion',
                'description' => 'Beautiful outdoor pavilion for intimate gatherings',
                'capacity' => 50,
                'base_rate' => 20000.00,
                'amenities' => ['Outdoor Setup', 'Garden View', 'Catering'],
            ],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($restaurant->branches as $branch) {
                $createdVenues = [];

                // Create 3 venues
                foreach ($venues as $venueData) {
                    $venue = Venue::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('name', $venueData['name'])
                        ->first();

                    if (!$venue) {
                        $venue = Venue::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'name' => $venueData['name'],
                            'description' => $venueData['description'],
                            'capacity' => $venueData['capacity'],
                            'base_rate' => $venueData['base_rate'],
                            'amenities' => $venueData['amenities'],
                            'is_active' => true,
                        ]);
                    }

                    $createdVenues[] = $venue;
                }

                // Create 3 events (one per venue)
                $eventCounter = 1;
                foreach ($createdVenues as $venue) {
                    $eventNumber = $this->generateUniqueEventNumber($restaurant->id, $branch->id, $eventCounter);

                    $event = Event::where('restaurant_id', $restaurant->id)
                        ->where('branch_id', $branch->id)
                        ->where('venue_id', $venue->id)
                        ->first();

                    if (!$event) {
                        $startTime = Carbon::now()->addDays(30)->setTime(18, 0);
                        $endTime = Carbon::now()->addDays(30)->setTime(22, 0);

                        $event = Event::create([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'venue_id' => $venue->id,
                            'event_number' => $eventNumber,
                            'event_name' => 'Sample Event ' . $eventCounter,
                            'description' => 'Sample event description for ' . $venue->name,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'expected_guests' => (int)($venue->capacity * 0.7),
                            'status' => EventStatus::TENTATIVE->value,
                            'package_amount' => $venue->base_rate,
                            'advance_paid' => $venue->base_rate * 0.3,
                        ]);
                    }

                    // Create 1 event charge per event
                    $chargeExists = EventCharge::where('event_id', $event->id)->exists();
                    if (!$chargeExists) {
                        EventCharge::create([
                            'event_id' => $event->id,
                            'type' => FolioLineType::OTHER->value,
                            'description' => 'Venue Rental Charge',
                            'amount' => $venue->base_rate,
                            'tax_amount' => $venue->base_rate * 0.18, // 18% tax
                            'discount_amount' => 0,
                            'net_amount' => $venue->base_rate * 1.18,
                            'posted_by' => null,
                        ]);
                    }

                    $eventCounter++;
                }
            }
        }
    }

    /**
     * Generate a unique event number
     */
    private function generateUniqueEventNumber(int $restaurantId, int $branchId, int $counter): string
    {
        $baseNumber = 'EVT-' . $branchId . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
        $eventNumber = $baseNumber;
        $maxAttempts = 1000;
        $attempt = 0;

        while (
            Event::where('event_number', $eventNumber)->exists() && $attempt < $maxAttempts
        ) {
            $attempt++;
            $eventNumber = $baseNumber . '-' . $attempt;
        }

        return $eventNumber;
    }
}


<?php

namespace Modules\Hotel\Database\Seeders;

use Illuminate\Database\Seeder;

class HotelDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!app()->environment('codecanyon')) {
            $this->call([
                HotelRoomTypeSeeder::class,
                HotelRoomSeeder::class,
                HotelGuestSeeder::class,
                HotelRatePlanSeeder::class,
                HotelTaxSeeder::class,
                HotelExtraServiceSeeder::class,
                HotelReservationSeeder::class,
                HotelVenueEventSeeder::class,
            ]);
        }
    }
}   

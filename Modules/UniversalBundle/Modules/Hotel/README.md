# Hotel Module for TableTrack

A comprehensive Hotel Booking + Room Service add-on that integrates seamlessly with TableTrack's existing restaurant management system.

## Overview

The Hotel module extends TableTrack to support hotel operations while reusing the core ordering engine, KOT/KDS system, and billing patterns. It adds hotel-specific features like room inventory, reservations, folio billing, and room service ordering.

## Features

### Core Functionality

1. **Room Inventory Management**
   - Room types with occupancy and amenities
   - Physical rooms with status tracking
   - Rate plans (EP, CP, MAP, AP)
   - Dynamic rate management by date ranges

2. **Reservation System**
   - Availability search by date range
   - Reservation creation and management
   - Cancellation handling with rules
   - Advance payment tracking

3. **Front Desk Operations**
   - Check-in/Check-out workflow
   - Walk-in guest support
   - Room assignment
   - Guest ID verification

4. **Folio Billing**
   - Guest bill linked to stay
   - Multiple posting types (room charges, F&B, minibar, etc.)
   - Payment tracking and settlement
   - Discount and adjustment support

5. **Room Service Integration**
   - Orders from room → KOT → post to folio
   - Pay now or post to room options
   - Seamless integration with existing order engine

6. **Housekeeping**
   - Task management by room
   - Status tracking (pending, in progress, completed)
   - Assignment to staff

7. **Banquet/Events**
   - Venue management
   - Event booking and scheduling
   - Package billing
   - F&B orders linked to events

## Installation

1. Ensure the module is in `Modules/Hotel` directory
2. Run migrations:
   ```bash
   php artisan migrate
   ```
3. Seed permissions:
   ```bash
   php artisan db:seed --class="Modules\Hotel\Database\Seeders\HotelPermissionSeeder"
   ```
4. Enable the module in your restaurant package settings
5. Assign permissions to roles as needed

## Database Structure

### Core Tables

- `hotel_room_types` - Room type definitions
- `hotel_rooms` - Physical rooms
- `hotel_rate_plans` - Rate plan types (EP, CP, MAP, AP)
- `hotel_rates` - Date-based rates
- `hotel_guests` - Guest information
- `hotel_reservations` - Reservations
- `hotel_reservation_rooms` - Reservation room assignments
- `hotel_stays` - Active stays (checked-in)
- `hotel_stay_guests` - Guests linked to stays
- `hotel_folios` - Guest bills
- `hotel_folio_lines` - Folio line items
- `hotel_folio_payments` - Payments against folios
- `hotel_housekeeping_tasks` - Housekeeping tasks
- `hotel_venues` - Banquet venues
- `hotel_events` - Event bookings
- `hotel_event_charges` - Event charges

### Order Integration

The module extends the `orders` table with:
- `context_type` - ENUM: RESTAURANT_TABLE, TAKEAWAY, DELIVERY, HOTEL_ROOM, BANQUET_EVENT
- `context_id` - Reference to stay_id or event_id
- `bill_to` - ENUM: PAY_NOW, POST_TO_ROOM
- `posted_to_folio_at` - Timestamp when posted to folio

## Usage

### Creating a Reservation

```php
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Guest;

$guest = Guest::create([...]);
$reservation = Reservation::create([
    'reservation_number' => Reservation::generateReservationNumber(branch()->id),
    'primary_guest_id' => $guest->id,
    'check_in_date' => '2025-01-20',
    'check_out_date' => '2025-01-22',
    // ... other fields
]);
```

### Check-in

```php
use Modules\Hotel\Entities\Stay;

$stay = Stay::create([
    'stay_number' => Stay::generateStayNumber(branch()->id),
    'reservation_id' => $reservation->id,
    'room_id' => $room->id,
    'check_in_at' => now(),
    // ... other fields
]);

// Create folio
$folio = Folio::create([
    'folio_number' => Folio::generateFolioNumber(branch()->id),
    'stay_id' => $stay->id,
    'opened_at' => now(),
]);
```

### Posting Order to Folio

```php
use Modules\Hotel\Helpers\HotelHelper;

// When order is completed and bill_to is POST_TO_ROOM
if ($order->bill_to === 'POST_TO_ROOM' && $order->context_type === 'HOTEL_ROOM') {
    $stay = Stay::find($order->context_id);
    $folio = $stay->folio;
    
    HotelHelper::postOrderToFolio($order, $folio);
}
```

### Check-out

```php
// Recalculate folio
$folio->recalculateTotals();

// Process payment
$folio->folioPayments()->create([...]);

// Close folio
$folio->update([
    'status' => FolioStatus::CLOSED,
    'closed_at' => now(),
    'closed_by' => auth()->id(),
]);

// Check out stay
$stay->update([
    'status' => StayStatus::CHECKED_OUT,
    'actual_checkout_at' => now(),
    'checked_out_by' => auth()->id(),
]);
```

## Permissions

The module includes the following permissions:

- **Front Desk**: Show Hotel Front Desk
- **Rooms**: Show/Create/Update/Delete Hotel Room
- **Room Types**: Show/Create/Update/Delete Hotel Room Type
- **Reservations**: Show/Create/Update/Delete/Cancel Hotel Reservation
- **Check-in/Check-out**: Check In/Out Hotel Guest
- **Folios**: Show/Post/Apply Discount/Apply Adjustment
- **Guests**: Show/Create/Update/Delete Hotel Guest
- **Rate Plans**: Show/Create/Update/Delete Hotel Rate Plan, Modify Hotel Rates
- **Housekeeping**: Show/Create/Update/Complete Hotel Housekeeping Task
- **Room Service**: Show/Create Hotel Room Service Order
- **Banquet**: Show/Create/Update/Delete Hotel Event

## Routes

All routes are prefixed with `/hotel` and require authentication:

- `/hotel/front-desk/dashboard` - Front desk dashboard
- `/hotel/rooms` - Room management
- `/hotel/reservations` - Reservation management
- `/hotel/check-in` - Check-in interface
- `/hotel/check-out` - Check-out interface
- `/hotel/folios/{stayId}` - View folio
- `/hotel/housekeeping` - Housekeeping tasks
- `/hotel/banquet` - Banquet/events
- `/hotel/room-service` - Room service orders

## Integration Points

### With Orders

- Orders can be created with `context_type=HOTEL_ROOM` and `context_id=stay_id`
- Orders can be posted to folio when `bill_to=POST_TO_ROOM`
- KOT/KDS works the same way for room service orders

### With Payments

- Folio payments can link to existing Payment records
- Supports multiple payment methods
- Tracks partial payments

### With Customers

- Guests can be linked to existing Customer records
- Guest information extends customer data

## Workflows

### Reservation → Check-in → Stay → Check-out

1. Create reservation with guest details
2. On check-in, create stay and assign room
3. Create folio for the stay
4. Post room charges and other services
5. On check-out, settle payments and close folio

### Room Service Order Flow

1. Guest orders from room (via QR/tablet/phone)
2. Order created with `context_type=HOTEL_ROOM`
3. Choose payment: Pay Now or Post to Room
4. If Post to Room, order is posted to folio when completed
5. KOT prints as usual
6. Charges appear on folio

## Notes

- Room inventory is managed at room type level during reservation
- Physical room assignment happens at check-in
- Folios are automatically created on check-in
- Room charges can be posted nightly via cron or upfront
- Credit limits can be set per stay to control posting

## Future Enhancements (V2+)

- OTA channel manager integration
- Rate shopping
- Complex revenue management
- Multi-property corporate chains
- Advanced reporting and analytics

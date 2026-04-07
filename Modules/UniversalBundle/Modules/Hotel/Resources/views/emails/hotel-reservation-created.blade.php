@component('mail::message')
# {{ __('hotel::modules.reservation.reservationReceiptTitle') }}

{{ __('hotel::modules.reservation.reservationLabel') }}: **{{ $reservation->reservation_number }}**  
{{ __('hotel::modules.reservation.guest') }}: **{{ $reservation->primaryGuest?->full_name }}**  
{{ __('hotel::modules.reservation.status') }}: **{{ $reservation->status?->label() ?? $reservation->status }}**

@component('mail::panel')
{{ __('hotel::modules.reservation.checkIn') }}: **{{ optional($reservation->check_in_date)->format('d M Y') }}**  
{{ __('hotel::modules.reservation.checkOut') }}: **{{ optional($reservation->check_out_date)->format('d M Y') }}**
@endcomponent

@if($reservation->relationLoaded('reservationRooms') ? $reservation->reservationRooms->isNotEmpty() : $reservation->reservationRooms()->exists())
@component('mail::table')
| {{ __('hotel::modules.reservation.roomType') }} | {{ __('hotel::modules.reservation.quantityShort') }} | {{ __('hotel::modules.reservation.rate') }} | {{ __('hotel::modules.reservation.total') }} |
|:--|--:|--:|--:|
@foreach($reservation->reservationRooms as $rr)
| {{ $rr->roomType?->name ?? '-' }} | {{ $rr->quantity ?? 0 }} | {{ currency_format($rr->rate ?? 0) }} | {{ currency_format($rr->total_amount ?? 0) }} |
@endforeach
@endcomponent
@endif

@component('mail::table')
|  |  |
|:--|--:|
| {{ __('hotel::modules.reservation.subtotal') }} | {{ currency_format($reservation->subtotal_before_tax ?? 0) }} |
| {{ __('hotel::modules.reservation.taxAmount') }} | {{ currency_format($reservation->tax_amount ?? 0) }} |
| **{{ __('hotel::modules.reservation.total') }}** | **{{ currency_format($reservation->total_amount ?? 0) }}** |
| {{ __('hotel::modules.reservation.advancePaid') }} | {{ currency_format($reservation->advance_paid ?? 0) }} |
@endcomponent

{{ __('hotel::modules.reservation.reservationCreatedEmailFooter') }}

@endcomponent


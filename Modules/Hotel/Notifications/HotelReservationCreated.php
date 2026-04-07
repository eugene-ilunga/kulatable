<?php

namespace Modules\Hotel\Notifications;

use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Hotel\Entities\Reservation;

class HotelReservationCreated extends BaseNotification
{
    public function __construct(protected Reservation $reservation)
    {
        $this->restaurant = $reservation->restaurant ?? $reservation->branch?->restaurant;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $build = parent::build($notifiable);

        return $build
            ->subject(__('hotel::modules.reservation.reservationCreatedEmailSubject', [
                'site_name' => $this->restaurant?->name ?? config('app.name'),
                'number' => $this->reservation->reservation_number,
            ]))
            ->markdown('hotel::emails.hotel-reservation-created', [
                'reservation' => $this->reservation,
                'restaurant' => $this->restaurant,
            ]);
    }
}


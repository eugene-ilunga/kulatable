<?php

namespace Modules\Inventory\Notifications;

use App\Models\Restaurant;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class DailyStockSummary extends BaseNotification
{
    use Queueable;

    public function __construct(
        $restaurant,
        public array $summary
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $restaurantName = $this->restaurant?->name;
        $tz = $this->restaurant->timezone ?? config('app.timezone');
        $date = now($tz);
        $dateString = $date->format('M d, Y');

        $subject = trans('inventory::modules.stock.daily_summary_subject', [
            'date' => $dateString,
        ]);

        if ($restaurantName) {
            $subject .= ' - '.$restaurantName;
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('inventory::emails.daily-stock-summary', [
                'restaurant' => $this->restaurant,
                'summary' => $this->summary,
                'date' => $date,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}


<?php

namespace Modules\Whatsapp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Entities\WhatsAppAutomatedSchedule;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessReservationFollowupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-reservation-followups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send reservation follow-up notifications (1 day after reservation)';

    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing reservation follow-ups...');

        // Get all enabled schedules for reservation followups
        $schedules = WhatsAppAutomatedSchedule::where('notification_type', 'reservation_followup')
            ->where('is_enabled', true)
            ->with('restaurant')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No reservation followup schedules found or enabled.');
            return Command::SUCCESS;
        }

        $processed = 0;
        $sent = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            try {
                // Check if schedule should be processed now
                if (!$this->shouldProcessSchedule($schedule)) {
                    continue;
                }

                $restaurant = $schedule->restaurant;
                if (!$restaurant) {
                    continue;
                }

                // Check if WhatsApp module is in restaurant's package
                if (function_exists('restaurant_modules')) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        Log::info('WhatsApp Reservation Followup Command: Skipping - WhatsApp module not in restaurant package', [
                            'schedule_id' => $schedule->id,
                            'restaurant_id' => $restaurant->id,
                        ]);
                        continue;
                    }
                }

                // Check if reservation followup notification preference is enabled
                $preference = WhatsAppNotificationPreference::where('restaurant_id', $restaurant->id)
                    ->where(function ($query) {
                        $query->where('notification_type', 'reservation_followup')
                            ->orWhere('notification_type', 'reservation_notification');
                    })
                    ->where('recipient_type', 'customer')
                    ->where('is_enabled', true)
                    ->first();

                if (!$preference) {
                    $this->warn("Notification preference not enabled for reservation_followup in restaurant {$restaurant->id}");
                    continue;
                }

                $processed++;

                // Find reservations that were completed 1 day ago (between 23-25 hours ago)
                $now = now($restaurant->timezone ?? 'UTC');
                $oneDayAgo = $now->copy()->subDay();
                $startTime = $oneDayAgo->copy()->subHour();
                $endTime = $oneDayAgo->copy()->addHour();

                $reservations = Reservation::whereHas('branch', function ($query) use ($restaurant) {
                        $query->where('restaurant_id', $restaurant->id);
                    })
                    ->whereBetween('reservation_date_time', [$startTime, $endTime])
                    ->whereIn('reservation_status', ['Completed', 'Confirmed', 'Served'])
                    ->whereHas('customer', function ($query) {
                        $query->whereNotNull('phone');
                    })
                    ->with(['customer', 'branch'])
                    ->get();

                foreach ($reservations as $reservation) {
                    try {
                        if (!$reservation->customer || !$reservation->customer->phone) {
                            continue;
                        }

                        // Skip if followup was already sent (you can add a flag in reservations table if needed)
                        $processed++;

                        $reservationDateTime = Carbon::parse($reservation->reservation_date_time);
                        
                        // Generate feedback link or discount offer
                        $feedbackMessage = __('whatsapp::app.wedLoveToHearYourFeedback');
                        // You can add a feedback link here: route('feedback', $reservation->id)

                        $variables = [
                            $reservation->customer->name ?? __('whatsapp::app.defaultCustomer'),
                            $reservationDateTime->format('d M, Y'),
                            $feedbackMessage,
                            $restaurant->name ?? '',
                        ];

                        $result = $this->notificationService->send(
                            $restaurant->id,
                            'reservation_followup',
                            $reservation->customer->phone,
                            $variables
                        );

                        if ($result['success']) {
                            $sent++;
                            $this->info("Sent reservation follow-up for reservation #{$reservation->id} to {$reservation->customer->phone}");
                        } else {
                            $failed++;
                            $this->error("Failed to send reservation follow-up for reservation #{$reservation->id}: {$result['error']}");
                        }

                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('WhatsApp Reservation Follow-up Error: ' . $e->getMessage(), [
                            'reservation_id' => $reservation->id ?? null,
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $this->error("Failed to send reservation follow-up for reservation #{$reservation->id}: " . $e->getMessage());
                    }
                }

                // Update last_sent_at
                $schedule->update(['last_sent_at' => now()]);

            } catch (\Exception $e) {
                $failed++;
                Log::error('WhatsApp Reservation Follow-up Processing Error: ' . $e->getMessage(), [
                    'schedule_id' => $schedule->id ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Error processing reservation followup schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        $this->info("Reservation follow-ups processed: {$processed}, sent: {$sent}, failed: {$failed}");
        return Command::SUCCESS;
    }

    /**
     * Check if schedule should be processed now.
     */
    protected function shouldProcessSchedule(WhatsAppAutomatedSchedule $schedule): bool
    {
        $restaurant = $schedule->restaurant;
        if (!$restaurant) {
            return false;
        }

        $now = now($restaurant->timezone ?? 'UTC');
        $scheduledTime = Carbon::parse($schedule->scheduled_time ?? '10:00');

        // Check if it's the right time (within 5 minutes)
        if ($now->format('H:i') !== $scheduledTime->format('H:i')) {
            return false;
        }

        // Check if already sent today
        if ($schedule->last_sent_at && $schedule->last_sent_at->isToday()) {
            return false;
        }

        switch ($schedule->schedule_type) {
            case 'daily':
                return true;

            case 'weekly':
                $scheduledDay = strtolower($schedule->scheduled_day ?? 'monday');
                $dayMap = [
                    'monday' => Carbon::MONDAY,
                    'tuesday' => Carbon::TUESDAY,
                    'wednesday' => Carbon::WEDNESDAY,
                    'thursday' => Carbon::THURSDAY,
                    'friday' => Carbon::FRIDAY,
                    'saturday' => Carbon::SATURDAY,
                    'sunday' => Carbon::SUNDAY,
                ];
                return isset($dayMap[$scheduledDay]) && $now->dayOfWeek === $dayMap[$scheduledDay];

            case 'monthly':
                $scheduledDay = (int) ($schedule->scheduled_day ?? 1);
                return $now->day === $scheduledDay;

            default:
                return false;
        }
    }
}

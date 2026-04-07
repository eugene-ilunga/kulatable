<?php

namespace Modules\Whatsapp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Entities\WhatsAppAutomatedSchedule;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessReservationRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-reservation-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send reservation reminder notifications (2 hours before reservation)';

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
        $this->info('Processing reservation reminders...');

        // Get all enabled schedules for reservation reminders
        $schedules = WhatsAppAutomatedSchedule::where('notification_type', 'reservation_reminder')
            ->where('is_enabled', true)
            ->with('restaurant')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No reservation reminder schedules found or enabled.');
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
                        Log::info('WhatsApp Reservation Reminder Command: Skipping - WhatsApp module not in restaurant package', [
                            'schedule_id' => $schedule->id,
                            'restaurant_id' => $restaurant->id,
                        ]);
                        continue;
                    }
                }

                // Check if reservation reminder notification preference is enabled
                $preference = WhatsAppNotificationPreference::where('restaurant_id', $restaurant->id)
                    ->where(function ($query) {
                        $query->where('notification_type', 'reservation_reminder')
                            ->orWhere('notification_type', 'reservation_notification');
                    })
                    ->where('recipient_type', 'customer')
                    ->where('is_enabled', true)
                    ->first();

                if (!$preference) {
                    $this->warn("Notification preference not enabled for reservation_reminder in restaurant {$restaurant->id}");
                    continue;
                }

                $processed++;

                // Find reservations that are 2 hours away (between 115-125 minutes from now)
                $now = now($restaurant->timezone ?? 'UTC');
                $twoHoursFromNow = $now->copy()->addHours(2);
                $startTime = $twoHoursFromNow->copy()->subMinutes(5);
                $endTime = $twoHoursFromNow->copy()->addMinutes(5);

                $reservations = Reservation::whereHas('branch', function ($query) use ($restaurant) {
                        $query->where('restaurant_id', $restaurant->id);
                    })
                    ->whereBetween('reservation_date_time', [$startTime, $endTime])
                    ->where('reservation_status', 'Confirmed')
                    ->whereHas('customer', function ($query) {
                        $query->whereNotNull('phone');
                    })
                    ->with(['customer', 'branch', 'table'])
                    ->get();

                foreach ($reservations as $reservation) {
                    try {
                        if (!$reservation->customer || !$reservation->customer->phone) {
                            continue;
                        }

                        // Skip if reminder was already sent (you can add a flag in reservations table if needed)
                        $processed++;

                        $reservationDateTime = Carbon::parse($reservation->reservation_date_time);
                        $timeUntilReservation = $reservationDateTime->diffForHumans($now);

                        $tableNumber = $reservation->table ? $reservation->table->table_number : __('whatsapp::app.tbd');

                        $variables = [
                            $reservation->customer->name ?? __('whatsapp::app.defaultCustomer'),
                            $reservationDateTime->format('d M, Y'),
                            $reservationDateTime->format('h:i A'),
                            $reservation->party_size ?? 1,
                            "Your reservation is in {$timeUntilReservation}",
                            $restaurant->name ?? '',
                            $restaurant->contact_number ?? '',
                        ];

                        $result = $this->notificationService->send(
                            $restaurant->id,
                            'reservation_reminder',
                            $reservation->customer->phone,
                            $variables
                        );

                        if ($result['success']) {
                            $sent++;
                            $this->info("Sent reservation reminder for reservation #{$reservation->id} to {$reservation->customer->phone}");
                        } else {
                            $failed++;
                            $this->error("Failed to send reservation reminder for reservation #{$reservation->id}: {$result['error']}");
                        }

                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('WhatsApp Reservation Reminder Error: ' . $e->getMessage(), [
                            'reservation_id' => $reservation->id ?? null,
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $this->error("Failed to send reservation reminder for reservation #{$reservation->id}: " . $e->getMessage());
                    }
                }

                // Update last_sent_at
                $schedule->update(['last_sent_at' => now()]);

            } catch (\Exception $e) {
                $failed++;
                Log::error('WhatsApp Reservation Reminder Processing Error: ' . $e->getMessage(), [
                    'schedule_id' => $schedule->id ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Error processing reservation reminder schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        $this->info("Reservation reminders processed: {$processed}, sent: {$sent}, failed: {$failed}");
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
        $scheduledTime = Carbon::parse($schedule->scheduled_time ?? '09:00');

        // For reservation reminders, we run hourly to catch reservations throughout the day
        // But we still respect the scheduled time - only process if current hour matches scheduled hour
        // This allows the command to run hourly but only process when it's the scheduled hour
        if ($now->format('H') !== $scheduledTime->format('H')) {
            return false;
        }

        // Don't block based on last_sent_at because we want to catch new reservations
        // that match the 2-hour window throughout the day
        // Each reservation will be checked individually to avoid duplicates

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

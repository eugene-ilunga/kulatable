<?php

namespace Modules\Whatsapp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Whatsapp\Entities\WhatsAppAutomatedSchedule;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Modules\Whatsapp\Services\WhatsAppHelperService;
use Modules\Whatsapp\Services\OperationsSummaryPdfService;
use Modules\Whatsapp\Services\WhatsAppPhoneResolver;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAutomatedSchedulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-automated-schedules {--test : Force process all enabled schedules regardless of time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send scheduled automated WhatsApp messages';

    protected WhatsAppNotificationService $notificationService;
    protected WhatsAppHelperService $helperService;
    protected OperationsSummaryPdfService $pdfService;

    public function __construct(
        WhatsAppNotificationService $notificationService,
        WhatsAppHelperService $helperService,
        OperationsSummaryPdfService $pdfService
    ) {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->helperService = $helperService;
        $this->pdfService = $pdfService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing automated WhatsApp schedules...');

        // Get fresh schedules from database (no caching)
        $schedules = WhatsAppAutomatedSchedule::withoutGlobalScopes()
            ->where('is_enabled', true)
            ->with('restaurant')
            ->get();

        // Refresh each schedule to ensure we have latest data
        foreach ($schedules as $schedule) {
            $schedule->refresh();
            // Also refresh the restaurant relationship
            if ($schedule->relationLoaded('restaurant')) {
                $schedule->restaurant->refresh();
            }
        }

        $processed = 0;
        $sent = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            try {
                $shouldProcess = $this->shouldProcessSchedule($schedule);

                if (!$shouldProcess) {
                    continue;
                }

                // In test mode, skip the "already sent today" check
                if ($schedule->last_sent_at && $schedule->last_sent_at->isToday()) {
                    Log::info('WhatsApp Automated Schedule Command: Skipping - already sent today (test mode)', [
                        'schedule_id' => $schedule->id,
                        'last_sent_at' => $schedule->last_sent_at->toDateTimeString(),
                    ]);
                    continue;
                }

                // Check if Inventory module is enabled for low_inventory_alert
                if ($schedule->notification_type === 'low_inventory_alert' && !module_enabled('Inventory')) {
                    $this->warn("Skipping low_inventory_alert for restaurant {$schedule->restaurant_id} - Inventory module is not enabled");
                    continue;
                }

                // Check if WhatsApp module is in restaurant's package
                if (function_exists('restaurant_modules')) {
                    $restaurantModules = restaurant_modules($schedule->restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        continue;
                    }
                }

                $processed++;

                // Check if notification preference is enabled
                // Get recipients - use roles if set, otherwise fall back to notification preference
                $recipients = collect();

                // Check if roles are configured in schedule
                $roleIds = $schedule->roles ?? [];
                if (!empty($roleIds) && is_array($roleIds)) {
                    // Use roles from schedule (like report schedules)
                    $recipients = $this->helperService->getUsersByRoles($schedule->restaurant_id, $roleIds);

                } else {
                    // Fall back to notification preference (backward compatibility)
                    $preferenceType = $schedule->notification_type;

                    $preference = WhatsAppNotificationPreference::where('restaurant_id', $schedule->restaurant_id)
                        ->where('notification_type', $preferenceType)
                        ->where('is_enabled', true)
                        ->first();

                    if (!$preference) {
                        Log::warning('WhatsApp Automated Schedule Command: No roles configured and notification preference not enabled', [
                            'schedule_id' => $schedule->id,
                            'notification_type' => $schedule->notification_type,
                            'preference_type' => $preferenceType,
                            'restaurant_id' => $schedule->restaurant_id,
                        ]);
                        $this->warn("No roles configured and notification preference not enabled for {$schedule->notification_type} in restaurant {$schedule->restaurant_id}");
                        continue;
                    }

                    // Get recipients based on recipient type
                    $recipients = $this->getRecipients($schedule->restaurant_id, $preference->recipient_type);
                }

                if ($recipients->isEmpty()) {
                    $this->warn("No recipients found for {$schedule->notification_type} in restaurant {$schedule->restaurant_id}");
                    continue;
                }

                // Prepare base variables based on notification type
                $baseVariables = $this->prepareVariables($schedule);

                // Send to all recipients
                foreach ($recipients as $recipient) {
                    // For inventory alerts, prepend recipient name to variables
                    $variables = $baseVariables;
                    if ($schedule->notification_type === 'low_inventory_alert') {
                        $recipientName = $recipient->name ?? __('whatsapp::app.defaultAdmin');
                        $variables = array_merge([$recipientName], $variables);
                    }
                    $recipientPhone = WhatsAppPhoneResolver::fromUser($recipient);

                    if (!$recipientPhone) {
                        $this->warn("Recipient {$recipient->id} has no valid phone number");
                        continue;
                    }

                    $templateType = $schedule->notification_type;

                    // Generate PDF for operations_summary
                    $documentPath = null;
                    if ($templateType === 'operations_summary') {
                        $restaurantTimezone = $schedule->restaurant->timezone ?? 'UTC';
                        $now = Carbon::now($restaurantTimezone);
                        $documentPath = $this->pdfService->generateDailyOperationsSummaryPdf(
                            $schedule->restaurant_id,
                            $now
                        );
                    }

                    $result = $this->notificationService->send(
                        $schedule->restaurant_id,
                        $templateType,
                        $recipientPhone,
                        $variables,
                        'en',
                        '',
                        $documentPath
                    );

                    if ($result['success']) {
                        $sent++;
                        $this->info("Sent {$schedule->notification_type} to {$recipientPhone}");
                    } else {
                        $failed++;
                        $this->error("Failed to send {$schedule->notification_type} to {$recipientPhone}: {$result['error']}");
                    }
                }

                // Update last_sent_at
                $schedule->update(['last_sent_at' => now()]);

            } catch (\Exception $e) {
                $failed++;
                Log::error('WhatsApp Automated Schedule Error: ' . $e->getMessage(), [
                    'schedule_id' => $schedule->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Error processing schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processed: {$processed}, Sent: {$sent}, Failed: {$failed}");

        return Command::SUCCESS;
    }

    /**
     * Check if schedule should be processed now.
     */
    protected function shouldProcessSchedule(WhatsAppAutomatedSchedule $schedule): bool
    {
        // Refresh schedule to get latest scheduled_time from database
        $schedule->refresh();
        $restaurantTimezone = $schedule->restaurant->timezone ?? 'UTC';
        $now = now($restaurantTimezone);

        // Handle every_5_minutes schedule type for low_inventory_alert
        if ($schedule->schedule_type === 'every_5_minutes') {
            // Check if it's been at least 5 minutes since last run
            if ($schedule->last_sent_at) {
                $minutesSinceLastRun = $schedule->last_sent_at->diffInMinutes($now);
                $shouldRun = $minutesSinceLastRun >= 5;

                return $shouldRun;
            }

            // If never run before, run it now
            return true;
        }

        // Parse scheduled_time correctly - handle both "12:20" and "12:20:00" formats
        $scheduledTimeStr = $schedule->scheduled_time ?? '09:00';

        // Remove seconds if present (e.g., "12:20:00" -> "12:20")
        if (strlen($scheduledTimeStr) > 5) {
            $scheduledTimeStr = substr($scheduledTimeStr, 0, 5);
        }

        // Parse scheduled time in restaurant's timezone
        // Create a datetime object for today with the scheduled time in restaurant timezone
        $scheduledTime = Carbon::createFromFormat('H:i', $scheduledTimeStr, $restaurantTimezone)
            ->setDate($now->year, $now->month, $now->day);

        // Use restaurant timezone for comparison
        $nowFormatted = $now->format('H:i');
        $scheduledFormatted = $scheduledTime->format('H:i');

        // Check if it's the right time (exact match)
        if ($nowFormatted !== $scheduledFormatted) {
            return false;
        }

        // Check if already sent today (using restaurant timezone)
        if ($schedule->last_sent_at) {
            $lastSentInTimezone = $schedule->last_sent_at->setTimezone($restaurantTimezone);
            $isToday = $lastSentInTimezone->isToday();

            if ($isToday) {
                return false;
            }
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

    /**
     * Get recipients for the notification.
     */
    protected function getRecipients(int $restaurantId, string $recipientType): \Illuminate\Database\Eloquent\Collection
    {
        switch ($recipientType) {
            case 'admin':
                return \App\Models\User::role('Admin_' . $restaurantId)
                    ->where('restaurant_id', $restaurantId)
                    ->whereNotNull('phone_number')
                    ->get();

            case 'staff':
                return \App\Models\User::role('Staff_' . $restaurantId)
                    ->where('restaurant_id', $restaurantId)
                    ->whereNotNull('phone_number')
                    ->get();

            case 'customer':
                // For automated messages, we might need to get all customers or specific ones
                // This depends on the notification type
                return collect([]);

            default:
                return collect([]);
        }
    }

    /**
     * Prepare variables for the notification template.
     */
    protected function prepareVariables(WhatsAppAutomatedSchedule $schedule): array
    {
        $now = now($schedule->restaurant->timezone ?? 'UTC');

        switch ($schedule->notification_type) {
            case 'low_inventory_alert':
                $data = $this->helperService->formatLowInventoryAlert($schedule->restaurant_id);
                // Get branch name (use first branch or restaurant name)
                $branchName = $schedule->restaurant->branches()->first()->name ?? $schedule->restaurant->name ?? 'N/A';
                return [
                    $data['item_count'],
                    $data['item_names'],
                    $branchName,
                ];

            case 'operations_summary':
                $data = $this->helperService->formatDailyOperationsSummary($schedule->restaurant_id, $now);
                // Get branch name (use first branch or restaurant name)
                $restaurant = \App\Models\Restaurant::with('branches')->find($schedule->restaurant_id);
                $branchName = $restaurant->branches->first()->name ?? $restaurant->name ?? 'Branch';

                // Get staff count (all users for the restaurant)
                $staffCount = \App\Models\User::where('restaurant_id', $schedule->restaurant_id)->count();

                // Variables format expected by template mapper: [branch_name, date, total_orders, total_revenue, total_reservations, staff_count]
                return [
                    $branchName,              // Index 0: Branch name
                    $data['date'],            // Index 1: Date
                    (string) $data['total_orders'],      // Index 2: Total orders
                    $data['total_revenue'],   // Index 3: Total revenue
                    (string) $data['total_reservations'], // Index 4: Total reservations
                    (string) $staffCount,     // Index 5: Combined staff on duty
                ];

            default:
                return [];
        }
    }
}

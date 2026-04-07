<?php

namespace Modules\Whatsapp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Whatsapp\Entities\WhatsAppReportSchedule;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Modules\Whatsapp\Services\WhatsAppHelperService;
use Modules\Whatsapp\Services\SalesReportPdfService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessReportSchedulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-report-schedules {--test : Force process all enabled schedules regardless of time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send scheduled WhatsApp reports';

    protected WhatsAppNotificationService $notificationService;
    protected WhatsAppHelperService $helperService;
    protected SalesReportPdfService $pdfService;

    public function __construct(
        WhatsAppNotificationService $notificationService,
        WhatsAppHelperService $helperService,
        SalesReportPdfService $pdfService
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
        $this->info('Processing WhatsApp report schedules...');

        $schedules = WhatsAppReportSchedule::where('is_enabled', true)
            ->with('restaurant')
            ->get();

        $processed = 0;
        $sent = 0;
        $failed = 0;

        $testMode = $this->option('test');

        foreach ($schedules as $schedule) {

            try {
                $shouldProcess = $testMode ? true : $this->shouldProcessSchedule($schedule);
                
                Log::info('WhatsApp Report Schedule Command: Checking schedule', [
                    'schedule_id' => $schedule->id,
                    'report_type' => $schedule->report_type,
                    'restaurant_id' => $schedule->restaurant_id,
                    'test_mode' => $testMode,
                    'should_process' => $shouldProcess,
                    'frequency' => $schedule->frequency,
                    'scheduled_time' => $schedule->scheduled_time,
                    'last_sent_at' => $schedule->last_sent_at?->toDateTimeString(),
                ]);
                
                if (!$shouldProcess) {
                    continue;
                }
                
                // In test mode, skip the "already sent today" check
                if (!$testMode && $schedule->last_sent_at && $schedule->last_sent_at->isToday()) {
                    Log::info('WhatsApp Report Schedule Command: Skipping - already sent today', [
                        'schedule_id' => $schedule->id,
                        'last_sent_at' => $schedule->last_sent_at->toDateTimeString(),
                    ]);
                    continue;
                }

                // Check if WhatsApp module is in restaurant's package
                if (function_exists('restaurant_modules')) {
                    $restaurantModules = restaurant_modules($schedule->restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        Log::info('WhatsApp Report Schedule Command: Skipping - WhatsApp module not in restaurant package', [
                            'schedule_id' => $schedule->id,
                            'restaurant_id' => $schedule->restaurant_id,
                        ]);
                        continue;
                    }
                }

                $processed++;

                // Get users by roles
                $roleIds = $schedule->roles ?? [];
                if (empty($roleIds)) {
                    $this->warn("No roles configured for report {$schedule->report_type} in restaurant {$schedule->restaurant_id}");
                    continue;
                }

                $recipients = $this->helperService->getUsersByRoles($schedule->restaurant_id, $roleIds);

                if ($recipients->isEmpty()) {
                    $this->warn("No recipients found for report {$schedule->report_type} in restaurant {$schedule->restaurant_id}");
                    continue;
                }

                // Prepare report data
                $variables = $this->prepareReportVariables($schedule);

                // Generate PDF for sales report (once for all recipients)
                $documentPath = null;
                $now = now($schedule->restaurant->timezone ?? 'UTC');
                $documentPath = $this->pdfService->generateSalesReportPdf(
                    $schedule->restaurant_id,
                    $schedule->report_type,
                    $now
                );
                
                if ($documentPath) {
                    Log::info('Sales Report PDF generated', [
                        'schedule_id' => $schedule->id,
                        'pdf_path' => $documentPath,
                    ]);
                } else {
                    Log::warning('Sales Report PDF generation failed, sending without document', [
                        'schedule_id' => $schedule->id,
                    ]);
                }

                // Send to all recipients
                foreach ($recipients as $recipient) {
                    // Get recipient phone number (combine phone_code and phone_number)
                    // User model uses 'phone_number' field (not 'phone' or 'mobile')
                    $recipientPhone = null;
                    
                    // Check phone_number field (User model field)
                    if (!empty($recipient->phone_number)) {
                        if (!empty($recipient->phone_code)) {
                            $recipientPhone = $recipient->phone_code . $recipient->phone_number;
                        } else {
                            $recipientPhone = $recipient->phone_number;
                        }
                    }

                    // Validate phone number is not null or empty
                    if (empty($recipientPhone) || !is_string($recipientPhone)) {
                        $this->warn("Skipping recipient {$recipient->name} (ID: {$recipient->id}) - no valid phone number");
                        $failed++;
                        continue;
                    }

                    // Ensure phone is a string and not null
                    $recipientPhone = (string) $recipientPhone;
                    
                    if (empty($recipientPhone)) {
                        $this->warn("Skipping recipient {$recipient->name} (ID: {$recipient->id}) - phone number is empty after conversion");
                        $failed++;
                        continue;
                    }

                    // Map report_type to notification_type for template mapper
                    $notificationType = match($schedule->report_type) {
                        'daily_sales' => 'daily_sales_report',
                        'weekly_sales' => 'weekly_sales_report',
                        'monthly_sales' => 'monthly_sales_report',
                        default => 'daily_sales_report',
                    };

                    $result = $this->notificationService->send(
                        $schedule->restaurant_id,
                        $notificationType, // Use mapped notification type (daily_sales_report, etc.)
                        $recipientPhone,
                        $variables,
                        'en',
                        '',
                        $documentPath // Pass PDF document path
                    );

                    if ($result['success']) {
                        $sent++;
                        $this->info("Sent {$schedule->report_type} to {$recipient->name} ({$recipientPhone})");
                    } else {
                        $failed++;
                        $this->error("Failed to send {$schedule->report_type} to {$recipient->name} ({$recipientPhone}): {$result['error']}");
                    }
                }

                // Update last_sent_at
                $schedule->update(['last_sent_at' => now()]);

            } catch (\Exception $e) {
                $failed++;
                Log::error('WhatsApp Report Schedule Error: ' . $e->getMessage(), [
                    'schedule_id' => $schedule->id,
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Error processing report schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processed: {$processed}, Sent: {$sent}, Failed: {$failed}");

        return Command::SUCCESS;
    }

    /**
     * Check if schedule should be processed now.
     */
    protected function shouldProcessSchedule(WhatsAppReportSchedule $schedule): bool
    {
        $restaurantTimezone = $schedule->restaurant->timezone ?? 'UTC';
        $now = now($restaurantTimezone);
        
        // Parse scheduled_time correctly - handle both "12:20" and "12:20:00" formats
        $scheduledTimeStr = $schedule->scheduled_time ?? '09:00';
        
        // Remove seconds if present (e.g., "12:20:00" -> "12:20")
        if (strlen($scheduledTimeStr) > 5) {
            $scheduledTimeStr = substr($scheduledTimeStr, 0, 5);
        }
        
        // Parse scheduled time in restaurant's timezone
        $scheduledTime = Carbon::createFromFormat('H:i', $scheduledTimeStr, $restaurantTimezone)
            ->setDate($now->year, $now->month, $now->day);
        
        // Use restaurant timezone for comparison
        $nowFormatted = $now->format('H:i');
        $scheduledFormatted = $scheduledTime->format('H:i');
        
        Log::info('WhatsApp Report Schedule Command: Time check', [
            'schedule_id' => $schedule->id,
            'restaurant_timezone' => $restaurantTimezone,
            'current_time' => $now->toDateTimeString(),
            'current_time_formatted' => $nowFormatted,
            'scheduled_time_raw' => $schedule->scheduled_time,
            'scheduled_time_parsed' => $scheduledTimeStr,
            'scheduled_time_formatted' => $scheduledFormatted,
            'time_match' => $nowFormatted === $scheduledFormatted,
        ]);
        
        // Check if it's the right time (exact match)
        if ($nowFormatted !== $scheduledFormatted) {
            return false;
        }
        
        // Check if already sent today (using restaurant timezone)
        if ($schedule->last_sent_at) {
            $lastSentInTimezone = $schedule->last_sent_at->setTimezone($restaurantTimezone);
            $isToday = $lastSentInTimezone->isToday();
            
            Log::info('WhatsApp Report Schedule Command: Last sent check', [
                'schedule_id' => $schedule->id,
                'last_sent_at' => $schedule->last_sent_at->toDateTimeString(),
                'last_sent_in_timezone' => $lastSentInTimezone->toDateTimeString(),
                'is_today' => $isToday,
            ]);
            
            if ($isToday) {
                Log::info('WhatsApp Report Schedule Command: Skipping - already sent today', [
                    'schedule_id' => $schedule->id,
                    'report_type' => $schedule->report_type,
                ]);
                return false;
            }
        }

        switch ($schedule->frequency) {
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
     * Prepare report variables based on report type and frequency.
     */
    protected function prepareReportVariables(WhatsAppReportSchedule $schedule): array
    {
        $now = now($schedule->restaurant->timezone ?? 'UTC');
        $restaurantName = $schedule->restaurant->name ?? '';

        switch ($schedule->report_type) {
            case 'daily_sales':
                $data = $this->helperService->formatDailySalesReport($schedule->restaurant_id, $now);
                // FormatSalesReport expects: [period, date, orders, revenue, net_revenue, tax_discount]
                return [
                    $data['date'], // [0] - Reporting period (date)
                    (string)$data['total_orders'], // [1] - Total orders processed
                    $data['total_revenue'], // [2] - Total revenue generated
                    $data['net_revenue'], // [3] - Net revenue after deductions
                    $data['total_tax'], // [4] - Tax amount
                    $data['total_discount'], // [5] - Discount amount
                ];

            case 'weekly_sales':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $data = $this->helperService->formatWeeklySalesReport($schedule->restaurant_id, $startDate, $endDate);
                // FormatSalesReport expects: [period, date_range, orders, revenue, net_revenue, tax_discount]
                return [
                    $data['period'], // [0] - Reporting period (date range)
                    (string)$data['total_orders'], // [1] - Total orders processed
                    $data['total_revenue'], // [2] - Total revenue generated
                    $data['net_revenue'], // [3] - Net revenue after deductions
                    $data['total_tax'], // [4] - Tax amount
                    $data['total_discount'], // [5] - Discount amount
                ];

            case 'monthly_sales':
                $data = $this->helperService->formatMonthlySalesReport($schedule->restaurant_id, $now);
                // FormatSalesReport expects: [period, month, orders, revenue, net_revenue, tax_discount]
                return [
                    $data['month'], // [0] - Reporting period (month)
                    (string)$data['total_orders'], // [1] - Total orders processed
                    $data['total_revenue'], // [2] - Total revenue generated
                    $data['net_revenue'], // [3] - Net revenue after deductions
                    $data['total_tax'], // [4] - Tax amount
                    $data['total_discount'], // [5] - Discount amount
                ];

            default:
                return [];
        }
    }
}


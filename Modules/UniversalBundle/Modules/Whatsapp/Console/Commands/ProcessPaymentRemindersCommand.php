<?php

namespace Modules\Whatsapp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Entities\WhatsAppAutomatedSchedule;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Modules\Whatsapp\Services\WhatsAppPhoneResolver;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessPaymentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-payment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send payment reminder notifications for pending/due payments';

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
        $this->info('Processing payment reminders...');

        // Get all enabled schedules for payment reminders
        $schedules = WhatsAppAutomatedSchedule::where('notification_type', 'payment_reminder')
            ->where('is_enabled', true)
            ->with('restaurant')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No payment reminder schedules found or enabled.');
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
                        Log::info('WhatsApp Payment Reminder Command: Skipping - WhatsApp module not in restaurant package', [
                            'schedule_id' => $schedule->id,
                            'restaurant_id' => $restaurant->id,
                        ]);
                        continue;
                    }
                }

                // Check if payment reminder notification preference is enabled
                $preference = WhatsAppNotificationPreference::where('restaurant_id', $restaurant->id)
                    ->where(function ($query) {
                        $query->where('notification_type', 'payment_reminder')
                            ->orWhere('notification_type', 'payment_notification');
                    })
                    ->where('recipient_type', 'customer')
                    ->where('is_enabled', true)
                    ->first();

                if (!$preference) {
                    $this->warn("Notification preference not enabled for payment_reminder in restaurant {$restaurant->id}");
                    continue;
                }

                $processed++;

                // Find orders with pending/due payments
                $orders = Order::whereHas('branch', function ($query) use ($restaurant) {
                        $query->where('restaurant_id', $restaurant->id);
                    })
                    ->where(function ($query) {
                        $query->where('status', 'payment_due')
                            ->orWhere('status', 'billed')
                            ->orWhereHas('payments', function ($q) {
                                $q->where('payment_method', 'due');
                            });
                    })
                    ->whereHas('customer', function ($query) {
                        $query->whereNotNull('phone');
                        $query->whereNotNull('phone_code');
                    })
                    ->with(['customer', 'branch', 'payments' => function ($query) {
                        $query->where('payment_method', 'due');
                    }])
                    ->get();

                foreach ($orders as $order) {
                    try {
                        // Skip if already paid
                        if ($order->status === 'paid' || ($order->amount_paid >= $order->total_amount)) {
                            continue;
                        }

                        // Calculate due amount
                        $dueAmount = $order->total_amount - ($order->amount_paid ?? 0);
                        if ($dueAmount <= 0) {
                            continue;
                        }

                        $customerPhone = WhatsAppPhoneResolver::fromCustomer($order->customer);
                        if (!$customerPhone) {
                            continue;
                        }

                        // Calculate due date (default to order date + 7 days, or use payment created_at + 7 days)
                        $duePayment = $order->payments()
                            ->where('payment_method', 'due')
                            ->latest()
                            ->first();
                        
                        if ($duePayment && $duePayment->created_at) {
                            $dueDate = Carbon::parse($duePayment->created_at)->addDays(7)->format('d M, Y');
                        } else {
                            $dueDate = Carbon::parse($order->date_time)->addDays(7)->format('d M, Y');
                        }

                        $currency = $restaurant->currency->currency_symbol ?? '';
                        $paymentLink = route('order.detail', $order->uuid) ?? '';

                        $variables = [
                            $order->customer->name ?? __('whatsapp::app.defaultCustomer'),
                            $currency . number_format($dueAmount, 2),
                            $order->show_formatted_order_number ?? 'N/A',
                            $dueDate,
                            $paymentLink ? __('whatsapp::app.payNow') . ": {$paymentLink}" : __('whatsapp::app.pleaseContactUsToMakePayment'),
                            $restaurant->contact_number ?? '',
                        ];

                        $result = $this->notificationService->send(
                            $restaurant->id,
                            'payment_reminder',
                            $customerPhone,
                            $variables
                        );

                        if ($result['success']) {
                            $sent++;
                            $this->info("Sent payment reminder for order #{$order->show_formatted_order_number} to {$customerPhone}");
                        } else {
                            $failed++;
                            $this->error("Failed to send payment reminder for order #{$order->show_formatted_order_number}: {$result['error']}");
                        }

                    } catch (\Exception $e) {
                        $failed++;
                        Log::error('WhatsApp Payment Reminder Error: ' . $e->getMessage(), [
                            'order_id' => $order->id ?? null,
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $this->error("Failed to send payment reminder for order #{$order->id}: " . $e->getMessage());
                    }
                }

                // Update last_sent_at
                $schedule->update(['last_sent_at' => now()]);

            } catch (\Exception $e) {
                $failed++;
                Log::error('WhatsApp Payment Reminder Processing Error: ' . $e->getMessage(), [
                    'schedule_id' => $schedule->id ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("Error processing payment reminder schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        $this->info("Payment reminders processed: {$processed}, sent: {$sent}, failed: {$failed}");
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

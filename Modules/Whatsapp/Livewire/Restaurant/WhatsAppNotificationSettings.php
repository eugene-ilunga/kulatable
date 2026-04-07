<?php

namespace Modules\Whatsapp\Livewire\Restaurant;

use Livewire\Component;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Entities\WhatsAppAutomatedSchedule;
use Modules\Whatsapp\Entities\WhatsAppReportSchedule;
use Modules\Whatsapp\Services\WhatsAppTemplateService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WhatsAppNotificationSettings extends Component
{
    use LivewireAlert;

    public $templates = [];
    public $notificationPreferences = [];
    public $automatedSchedules = [];
    public $reportSchedules = [];
    public $availableRoles = [];
    public $restaurantId;
    public $notificationSections = [];
    public $lowStockTemplate = null;
    public $automatedTemplates = [];

    protected function notifyAutoSaved()
    {
        $this->alert('success', __('whatsapp::app.settingsSaved'));
    }

    public function mount()
    {
        // Check if WhatsApp module is enabled at both system and restaurant level
        if (!function_exists('module_enabled') || !module_enabled('Whatsapp')) {
            abort(404, 'WhatsApp module is not enabled');
        }
        
        if (!function_exists('restaurant_modules') || !in_array('Whatsapp', restaurant_modules())) {
            abort(404, 'WhatsApp module is not available for this restaurant');
        }
        
        // Check if WhatsApp is enabled in superadmin settings
        $globalWhatsAppSetting = \Modules\Whatsapp\Entities\WhatsAppSetting::whereNull('restaurant_id')->first();
        if (!$globalWhatsAppSetting || !$globalWhatsAppSetting->is_enabled) {
            abort(404, 'WhatsApp is disabled by administrator');
        }
        
        $user = Auth::user();
        $this->restaurantId = $user ? $user->restaurant_id : 1;
        
        // Load templates directly from WhatsAppTemplateDefinition
        try {
            $templates = \Modules\Whatsapp\Entities\WhatsAppTemplateDefinition::where('is_active', true)->get();
            $this->templates = $templates ?? collect();
        } catch (\Exception $e) {
            Log::error('Error loading templates', ['error' => $e->getMessage()]);
            $this->templates = collect();
        }
        
        // Ensure low_inventory_alert template exists
        $templateCollection = collect($this->templates);
        $lowStockTemplate = $templateCollection->where('notification_type', 'low_inventory_alert')->first();
        if (!$lowStockTemplate) {
            // Create the template if it doesn't exist
            $templateData = [
                'notification_type' => 'low_inventory_alert',
                'template_name' => 'Low Stock Alert',
                'description' => 'Alert when inventory items are running low',
                'category' => 'automated',
                'is_active' => true,
                'template_json' => json_encode([
                    'name' => 'low_stock_alert',
                    'language' => 'en',
                    'category' => 'MARKETING',
                    'components' => [
                        [
                            'type' => 'BODY',
                            'text' => 'Low Stock Alert: {{1}} items are running low in inventory. Please restock soon.'
                        ]
                    ]
                ])
            ];
            
            $lowStockTemplate = \Modules\Whatsapp\Entities\WhatsAppTemplateDefinition::create($templateData);
            
            // Reload templates
            $this->templates = \Modules\Whatsapp\Entities\WhatsAppTemplateDefinition::where('is_active', true)->get();
        }
        
        $this->organizeTemplates();
        
        // Load available roles
        $this->loadAvailableRoles();

        // Backfill missing recipient-specific preference rows from the older broken
        // restaurant settings UI, which saved several customer-facing toggles as staff.
        $this->syncNotificationPreferencesStructure();
        
        // Load existing preferences and schedules
        $this->loadNotificationPreferences();
        $this->loadAutomatedSchedules();
        $this->loadReportSchedules();
    }

    protected function organizeTemplates(): void
    {
        $templatesCollection = collect($this->templates);

        $this->lowStockTemplate = $templatesCollection->firstWhere('notification_type', 'low_inventory_alert');

        $this->notificationSections = [
            [
                'recipient' => 'customer',
                'title' => __('whatsapp::app.customer') . ' Notifications',
                'description' => 'Configure notifications that will be sent to customers when order, payment, reservation, and delivery events occur',
                'templates' => $templatesCollection
                    ->whereIn('notification_type', ['order_notifications', 'payment_notification', 'reservation_notification', 'delivery_notification'])
                    ->values(),
            ],
            [
                'recipient' => 'admin',
                'title' => __('whatsapp::app.admin') . ' Notifications',
                'description' => 'Configure notifications that will be sent to restaurant admins for new orders and reservation alerts',
                'templates' => $templatesCollection
                    ->whereIn('notification_type', ['new_order_alert'])
                    ->values(),
            ],
            [
                'recipient' => 'staff',
                'title' => __('whatsapp::app.staffNotifications'),
                'description' => __('whatsapp::app.configureStaffNotifications'),
                'templates' => $templatesCollection
                    ->whereIn('notification_type', ['new_order_alert', 'kitchen_notification', 'staff_notification'])
                    ->values(),
            ],
            [
                'recipient' => 'delivery',
                'title' => __('whatsapp::app.delivery') . ' Notifications',
                'description' => 'Configure notifications that will be sent to delivery executives for new delivery orders and delivery status updates',
                'templates' => $templatesCollection
                    ->whereIn('notification_type', ['new_order_alert', 'delivery_notification', 'order_notifications'])
                    ->values(),
            ],
        ];

        $this->automatedTemplates = $templatesCollection
            ->whereIn('notification_type', ['subscription_expiry_reminder', 'operations_summary'])
            ->unique('notification_type')
            ->values()
            ->all();
    }

    protected function syncNotificationPreferencesStructure(): void
    {
        $requiredPreferences = [
            ['notification_type' => 'order_notifications', 'recipient_type' => 'customer'],
            ['notification_type' => 'payment_notification', 'recipient_type' => 'customer'],
            ['notification_type' => 'reservation_notification', 'recipient_type' => 'customer'],
            ['notification_type' => 'delivery_notification', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_notifications', 'recipient_type' => 'delivery'],
            ['notification_type' => 'new_order_alert', 'recipient_type' => 'admin'],
            ['notification_type' => 'new_order_alert', 'recipient_type' => 'staff'],
            ['notification_type' => 'kitchen_notification', 'recipient_type' => 'staff'],
            ['notification_type' => 'staff_notification', 'recipient_type' => 'staff'],
            ['notification_type' => 'new_order_alert', 'recipient_type' => 'delivery'],
            ['notification_type' => 'delivery_notification', 'recipient_type' => 'delivery'],
        ];

        $legacyRecipientMap = [
            'order_notifications' => ['from' => 'staff', 'to' => 'customer'],
            'payment_notification' => ['from' => 'staff', 'to' => 'customer'],
            'reservation_notification' => ['from' => 'staff', 'to' => 'customer'],
        ];

        foreach ($requiredPreferences as $preference) {
            $attributes = [
                'restaurant_id' => $this->restaurantId,
                'notification_type' => $preference['notification_type'],
                'recipient_type' => $preference['recipient_type'],
            ];

            $existing = WhatsAppNotificationPreference::where($attributes)->first();
            if ($existing) {
                continue;
            }

            $defaultValue = false;
            $legacyMap = $legacyRecipientMap[$preference['notification_type']] ?? null;

            if ($legacyMap && $legacyMap['to'] === $preference['recipient_type']) {
                $legacyPreference = WhatsAppNotificationPreference::where('restaurant_id', $this->restaurantId)
                    ->where('notification_type', $preference['notification_type'])
                    ->where('recipient_type', $legacyMap['from'])
                    ->first();

                if ($legacyPreference) {
                    $defaultValue = (bool) $legacyPreference->is_enabled;
                }
            }

            if (
                $preference['notification_type'] === 'order_notifications' &&
                $preference['recipient_type'] === 'delivery'
            ) {
                $deliveryFallback = WhatsAppNotificationPreference::where('restaurant_id', $this->restaurantId)
                    ->where('notification_type', 'delivery_notification')
                    ->where('recipient_type', 'delivery')
                    ->first();

                if ($deliveryFallback) {
                    $defaultValue = (bool) $deliveryFallback->is_enabled;
                }
            }

            if (
                $preference['notification_type'] === 'new_order_alert' &&
                $preference['recipient_type'] === 'staff'
            ) {
                $staffFallback = WhatsAppNotificationPreference::where('restaurant_id', $this->restaurantId)
                    ->whereIn('notification_type', ['staff_notification', 'kitchen_notification'])
                    ->where('recipient_type', 'staff')
                    ->where('is_enabled', true)
                    ->first();

                if ($staffFallback) {
                    $defaultValue = true;
                }
            }

            WhatsAppNotificationPreference::create([
                ...$attributes,
                'is_enabled' => $defaultValue,
            ]);
        }
    }

    protected function loadAvailableRoles()
    {
        $roles = \App\Models\Role::where('name', '!=', 'customer')
            ->select('id', 'name')
            ->get();
        
        // Clean up role names - remove numbers and underscores, capitalize
        $this->availableRoles = $roles->map(function($role) {
            $cleanName = ucfirst(str_replace(['_', '-'], ' ', preg_replace('/\d+/', '', $role->name)));
            return [
                'id' => $role->id,
                'name' => $cleanName
            ];
        })->toArray();
    }

    protected function loadNotificationPreferences()
    {
        // Clear existing preferences first
        $this->notificationPreferences = [];
        
        // Force fresh query without any caching
        $preferences = WhatsAppNotificationPreference::where('restaurant_id', $this->restaurantId)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->fresh();
        
        foreach ($preferences as $preference) {
            $key = $preference->notification_type . '_' . $preference->recipient_type;
            $this->notificationPreferences[$key] = (bool) $preference->is_enabled;
        }
        
    }
    

    protected function loadAutomatedSchedules()
    {
        // Initialize automatedSchedules as empty array first
        $this->automatedSchedules = [];
        
        // Get all automated notification types from templates
        $templatesCollection = is_array($this->templates) ? collect($this->templates) : $this->templates;
        $automatedTemplates = $templatesCollection->where('category', 'automated');
        $otherAutomated = $templatesCollection->whereIn('notification_type', ['subscription_expiry_reminder', 'operations_summary']);
        // low_inventory_alert is handled separately in the blade template but needs to be initialized
        $allAutomatedTypes = $automatedTemplates->merge($otherAutomated)->pluck('notification_type')->unique()->toArray();
        
        // Always ensure low_inventory_alert is included for initialization (it won't appear twice in UI)
        if (!in_array('low_inventory_alert', $allAutomatedTypes)) {
            $allAutomatedTypes[] = 'low_inventory_alert';
        }
        
        
        // Initialize ALL automated schedules with default values FIRST
        foreach ($allAutomatedTypes as $notificationType) {
            $this->automatedSchedules[$notificationType] = [
                'is_enabled' => false,
                'schedule_type' => 'daily',
                'scheduled_time' => '09:00',
                'scheduled_day' => '',
                'roles' => [],
            ];
        }
        
        // Load existing schedules from database and override defaults
        $schedules = WhatsAppAutomatedSchedule::where('restaurant_id', $this->restaurantId)->get();
        
        foreach ($schedules as $schedule) {
            // Only override if the notification type exists in our templates
            if (isset($this->automatedSchedules[$schedule->notification_type])) {
                // Convert 'cron' to 'daily' if it exists (cron is no longer supported)
                $scheduleType = ($schedule->schedule_type === 'cron') ? 'daily' : $schedule->schedule_type;
                
                // Ensure roles are integers
                $roles = $schedule->roles ?? [];
                if (is_array($roles)) {
                    $roles = array_map('intval', $roles);
                }
                
                $this->automatedSchedules[$schedule->notification_type] = [
                    'is_enabled' => $schedule->is_enabled,
                    'schedule_type' => $scheduleType,
                    'scheduled_time' => $schedule->scheduled_time ?? '09:00',
                    'scheduled_day' => $schedule->scheduled_day ?? '',
                    'roles' => $roles,
                ];
            }
        }
    }

    protected function loadReportSchedules()
    {
        $schedules = WhatsAppReportSchedule::where('restaurant_id', $this->restaurantId)->get();
        
        $reportTypes = ['daily_sales', 'weekly_sales', 'monthly_sales'];
        
        // Initialize with defaults
        foreach ($reportTypes as $reportType) {
            $this->reportSchedules[$reportType] = [
                'is_enabled' => false,
                'frequency' => str_replace('_sales', '', $reportType),
                'scheduled_time' => '09:00',
                'scheduled_day' => '',
                'recipients' => [],
            ];
        }
        
        // Override with existing data
        foreach ($schedules as $schedule) {
            if (isset($this->reportSchedules[$schedule->report_type])) {
                $recipients = $schedule->roles ?? [];
                if (is_array($recipients)) {
                    $recipients = array_map('intval', $recipients);
                }

                $this->reportSchedules[$schedule->report_type] = [
                    'is_enabled' => $schedule->is_enabled,
                    'frequency' => $schedule->frequency,
                    'scheduled_time' => $schedule->scheduled_time ?? '09:00',
                    'scheduled_day' => $schedule->scheduled_day ?? '',
                    'recipients' => $recipients, // Use 'roles' from database
                ];
            }
        }
    }

    // NOTIFICATION PREFERENCES - Auto-save when property changes
    public function updatedNotificationPreferences($value, $key)
    {
        // Parse the key to get notification type and recipient type
        $parts = explode('_', $key);
        if (count($parts) >= 2) {
            $recipientType = array_pop($parts);
            $notificationType = implode('_', $parts);
            
            
            // Ensure the property is set to the new value
            $this->notificationPreferences[$key] = (bool) $value;
            
            // Save to database
            $saved = $this->saveNotificationPreference($notificationType, $recipientType);
            if ($saved) {
                $this->notifyAutoSaved();
            }
            
            // Prevent component refresh after save
            $this->dispatch('preference-updated', ['key' => $key, 'value' => $value]);
        }
    }
    
    // Keep the old method for backward compatibility
    public function toggleNotificationPreference($notificationType, $recipientType)
    {
        $key = $notificationType . '_' . $recipientType;
        
        
        // Initialize if not set - load from database first
        if (!isset($this->notificationPreferences[$key])) {
            $existing = WhatsAppNotificationPreference::where('restaurant_id', $this->restaurantId)
                ->where('notification_type', $notificationType)
                ->where('recipient_type', $recipientType)
                ->first();
            $this->notificationPreferences[$key] = $existing ? (bool) $existing->is_enabled : false;
        }
        
        // Toggle the value
        $this->notificationPreferences[$key] = !($this->notificationPreferences[$key] ?? false);
        
        // Save immediately
        $this->saveNotificationPreference($notificationType, $recipientType);
    }

    protected function saveNotificationPreference($notificationType, $recipientType)
    {
        $key = $notificationType . '_' . $recipientType;
        $isEnabled = $this->notificationPreferences[$key] ?? false;
        
        try {
            // Use DB transaction to ensure consistency
            DB::beginTransaction();
            
            $preference = WhatsAppNotificationPreference::updateOrCreate(
                [
                    'restaurant_id' => $this->restaurantId,
                    'notification_type' => $notificationType,
                    'recipient_type' => $recipientType,
                ],
                [
                    'is_enabled' => $isEnabled,
                ]
            );
            
            DB::commit();
            
            // Force refresh the preference to ensure it's saved
            $preference->refresh();
            
            
            // Update the local property to match database
            $this->notificationPreferences[$key] = (bool) $preference->is_enabled;
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving notification preference: ' . $e->getMessage());
            return false;
        }
    }

    // AUTOMATED SCHEDULES
    public function toggleAutomatedSchedule($notificationType)
    {
        Log::info('Toggle Automated Schedule', [
            'notification_type' => $notificationType,
            'current_state' => $this->automatedSchedules[$notificationType] ?? 'NOT SET',
        ]);
        
        // Initialize if not exists
        if (!isset($this->automatedSchedules[$notificationType])) {
            $this->automatedSchedules[$notificationType] = [
                'is_enabled' => false,
                'schedule_type' => 'daily',
                'scheduled_time' => '09:00',
                'scheduled_day' => '',
                'roles' => [],
            ];
        }
        
        // Toggle the enabled state
        $this->automatedSchedules[$notificationType]['is_enabled'] = 
            !($this->automatedSchedules[$notificationType]['is_enabled'] ?? false);
        
        // Special handling for low_inventory_alert - run every 5 minutes instead of specific time
        if ($notificationType === 'low_inventory_alert') {
            $this->automatedSchedules[$notificationType]['schedule_type'] = 'every_5_minutes';
            $this->automatedSchedules[$notificationType]['scheduled_time'] = null; // No specific time needed
        } else {
            // For other schedules, ensure required fields are set
            if (empty($this->automatedSchedules[$notificationType]['scheduled_time'])) {
                $this->automatedSchedules[$notificationType]['scheduled_time'] = '09:00';
            }
            if (empty($this->automatedSchedules[$notificationType]['schedule_type'])) {
                $this->automatedSchedules[$notificationType]['schedule_type'] = 'daily';
            }
        }
        
        // Save immediately
        $this->saveAutomatedSchedule($notificationType);
        $this->notifyAutoSaved();
    }

    public function updateAutomatedScheduleField($notificationType, $field, $value)
    {
        
        // Initialize if not exists
        if (!isset($this->automatedSchedules[$notificationType])) {
            $this->automatedSchedules[$notificationType] = [
                'is_enabled' => false,
                'schedule_type' => 'daily',
                'scheduled_time' => '09:00',
                'scheduled_day' => '',
                'roles' => [],
            ];
        }
        
        // Update the field
        $this->automatedSchedules[$notificationType][$field] = $value;
        
        // Save immediately
        $this->saveAutomatedSchedule($notificationType);
        $this->notifyAutoSaved();
    }

    protected function saveAutomatedSchedule($notificationType)
    {
        $schedule = $this->automatedSchedules[$notificationType] ?? null;
        
        if (!$schedule) {
            Log::warning('Attempted to save non-existent schedule', ['notification_type' => $notificationType]);
            return;
        }
        
        // Ensure roles are integers
        $roles = $schedule['roles'] ?? [];
        if (is_array($roles)) {
            $roles = array_map('intval', $roles);
        }
        
        $data = [
            'restaurant_id' => $this->restaurantId,
            'notification_type' => $notificationType,
            'schedule_type' => $schedule['schedule_type'] ?? 'daily',
            'is_enabled' => $schedule['is_enabled'] ?? false,
            'scheduled_time' => $schedule['scheduled_time'], // Allow null for every_5_minutes
            'scheduled_day' => $schedule['scheduled_day'] ?? null,
            'roles' => $roles,
        ];
        
        try {
            $savedSchedule = WhatsAppAutomatedSchedule::updateOrCreate(
                [
                    'restaurant_id' => $this->restaurantId,
                    'notification_type' => $notificationType,
                ],
                $data
            );
            
            Log::info('Automated Schedule Saved', [
                'notification_type' => $notificationType,
                'schedule_id' => $savedSchedule->id,
                'is_enabled' => $savedSchedule->is_enabled,
                'scheduled_time' => $savedSchedule->scheduled_time,
                'wasRecentlyCreated' => $savedSchedule->wasRecentlyCreated,
            ]);
            
            return $savedSchedule;
        } catch (\Exception $e) {
            Log::error('Error saving automated schedule', [
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    // REPORT SCHEDULES
    public function updateReportScheduleField($reportType, $field, $value)
    {
        // Initialize if not exists
        if (!isset($this->reportSchedules[$reportType])) {
            $this->reportSchedules[$reportType] = [
                'is_enabled' => false,
                'frequency' => str_replace('_sales', '', $reportType),
                'scheduled_time' => '09:00',
                'scheduled_day' => '',
                'recipients' => [],
            ];
        }
        
        // Update the specific field
        $this->reportSchedules[$reportType][$field] = $value;
        
        // Save immediately if the schedule is enabled
        if ($this->reportSchedules[$reportType]['is_enabled'] ?? false) {
            $this->saveReportSchedule($reportType);
            $this->notifyAutoSaved();
        }
    }

    public function toggleReportSchedule($reportType)
    {
        Log::info('Toggle Report Schedule', [
            'report_type' => $reportType,
            'current_state' => $this->reportSchedules[$reportType] ?? 'NOT SET',
        ]);
        
        // Initialize if not exists
        if (!isset($this->reportSchedules[$reportType])) {
            $this->reportSchedules[$reportType] = [
                'is_enabled' => false,
                'frequency' => str_replace('_sales', '', $reportType),
                'scheduled_time' => '09:00',
                'scheduled_day' => '',
                'recipients' => [],
            ];
        }
        
        // Toggle the enabled state
        $this->reportSchedules[$reportType]['is_enabled'] = 
            !($this->reportSchedules[$reportType]['is_enabled'] ?? false);
        
        // Save immediately
        $this->saveReportSchedule($reportType);
        $this->notifyAutoSaved();
    }

    protected function saveReportSchedule($reportType)
    {
        $schedule = $this->reportSchedules[$reportType] ?? null;
        
        if (!$schedule) {
            Log::warning('Attempted to save non-existent report schedule', ['report_type' => $reportType]);
            return;
        }
        
        $recipients = $schedule['recipients'] ?? [];
        if (is_array($recipients)) {
            $recipients = array_map('intval', $recipients);
        }

        $data = [
            'restaurant_id' => $this->restaurantId,
            'report_type' => $reportType,
            'frequency' => $schedule['frequency'],
            'is_enabled' => $schedule['is_enabled'] ?? false,
            'scheduled_time' => $schedule['scheduled_time'] ?? '09:00',
            'scheduled_day' => $schedule['scheduled_day'] ?? null,
            'roles' => $recipients, // Save recipients as roles in database
        ];
        
        try {
            $savedSchedule = WhatsAppReportSchedule::updateOrCreate(
                [
                    'restaurant_id' => $this->restaurantId,
                    'report_type' => $reportType,
                ],
                $data
            );
            
            Log::info('Report Schedule Saved', [
                'report_type' => $reportType,
                'schedule_id' => $savedSchedule->id,
                'is_enabled' => $savedSchedule->is_enabled,
                'saved_roles' => $savedSchedule->roles,
            ]);
            
            return $savedSchedule;
        } catch (\Exception $e) {
            Log::error('Error saving report schedule: ' . $e->getMessage());
            throw $e;
        }
    }

    // SAVE ALL (Legacy method for bulk save)
    public function saveAll()
    {
        try {
            DB::beginTransaction();
            
            // Save all notification preferences
            foreach ($this->notificationPreferences as $key => $isEnabled) {
                $parts = explode('_', $key);
                if (count($parts) >= 2) {
                    $recipientType = array_pop($parts);
                    $notificationType = implode('_', $parts);
                    
                    WhatsAppNotificationPreference::updateOrCreate(
                        [
                            'restaurant_id' => $this->restaurantId,
                            'notification_type' => $notificationType,
                            'recipient_type' => $recipientType,
                        ],
                        [
                            'is_enabled' => $isEnabled,
                        ]
                    );
                }
            }
            
            // Save all automated schedules
            foreach ($this->automatedSchedules as $notificationType => $schedule) {
                $this->saveAutomatedSchedule($notificationType);
            }
            
            // Save all report schedules
            foreach ($this->reportSchedules as $reportType => $schedule) {
                $this->saveReportSchedule($reportType);
            }
            
            DB::commit();
            
            $this->alert('success', __('whatsapp::app.settingsSaved'));
            
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error saving WhatsApp settings: ' . $e->getMessage());
            
            $this->alert('error', __('whatsapp::app.errorSavingSettings'));
        }
    }

    public function render()
    {
        return view('whatsapp::livewire.restaurant.whatsapp-notification-settings');
    }
}

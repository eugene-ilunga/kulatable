<?php

namespace Modules\Whatsapp\Services;

use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Entities\WhatsAppNotificationLog;
use Modules\Whatsapp\Entities\WhatsAppSetting;
use Modules\Whatsapp\Entities\WhatsAppTemplateDefinition;
use Modules\Whatsapp\Entities\WhatsAppTemplateMapping;
use Modules\Whatsapp\Services\WhatsAppTemplateMapper;

class WhatsAppNotificationService
{
    /** @var array<int, \App\Models\Restaurant|null> */
    private static array $restaurantByIdForPackageCheck = [];

    protected WhatsAppService $whatsappService;
    protected WhatsAppTemplateService $templateService;

    public function __construct(
        WhatsAppService $whatsappService,
        WhatsAppTemplateService $templateService
    ) {
        $this->whatsappService = $whatsappService;
        $this->templateService = $templateService;
    }

    /**
     * Send WhatsApp notification.
     *
     * @param int $restaurantId
     * @param string $notificationType
     * @param string $recipientPhone
     * @param array $variables
     * @param string $language
     * @param string $recipientType Optional: 'customer', 'admin', 'staff', 'delivery' - helps format variables correctly
     * @param string|null $documentPath Optional: Full path to PDF file to attach as document media
     * @return array
     */
    public function send(
        int $restaurantId,
        string $notificationType,
        string $recipientPhone,
        array $variables = [],
        string $language = 'en',
        string $recipientType = '',
        ?string $documentPath = null
    ): array {
        // Check if WhatsApp module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            if (! array_key_exists($restaurantId, self::$restaurantByIdForPackageCheck)) {
                self::$restaurantByIdForPackageCheck[$restaurantId] = \App\Models\Restaurant::find($restaurantId);
            }
            $restaurant = self::$restaurantByIdForPackageCheck[$restaurantId];
            if ($restaurant) {
                $restaurantModules = restaurant_modules($restaurant);
                if (!in_array('Whatsapp', $restaurantModules)) {
                    return [
                        'success' => false,
                        'error' => 'WhatsApp module is not available in your package',
                    ];
                }
            }
        }

        // Get WhatsApp settings - first try restaurant-specific, then fall back to global (restaurant_id = null)
        $setting = WhatsAppSetting::where('restaurant_id', $restaurantId)->first();
        
        // If no restaurant-specific setting or it's not configured, try global setting
        if (!$setting || !$setting->isConfigured()) {
            $setting = WhatsAppSetting::whereNull('restaurant_id')->first();
        }

        if (!$setting || !$setting->isConfigured()) {
            Log::warning("WhatsApp: Not configured | Type: {$notificationType} | Phone: {$recipientPhone}");
            return [
                'success' => false,
                'error' => 'WhatsApp is not configured or enabled for this restaurant.',
            ];
        }

        // Map to consolidated template and format variables
        $consolidatedTemplateType = WhatsAppTemplateMapper::getConsolidatedTemplateName($notificationType);
        $formattedVariables = $this->formatVariablesForConsolidatedTemplate($notificationType, $variables, $recipientType);

        // Get template mapping (now using consolidated template type)
        $templateName = $this->templateService->getRestaurantTemplateName(
            $restaurantId,
            $consolidatedTemplateType,
            $language
        );

        if (!$templateName) {
            Log::error("WhatsApp: Template not found | Type: {$notificationType} | Phone: {$recipientPhone}");
            return [
                'success' => false,
                'error' => "Template mapping not found for notification type: {$consolidatedTemplateType}",
            ];
        }

        $supportsDocumentHeader = $this->templateSupportsDocumentHeader($consolidatedTemplateType);

        // Create log entry
        $log = WhatsAppNotificationLog::create([
            'restaurant_id' => $restaurantId,
            'notification_type' => $notificationType,
            'recipient_phone' => $recipientPhone,
            'template_name' => $templateName,
            'variables' => $variables,
            'status' => 'pending',
        ]);

        // If document path provided, upload media and send with document
        $mediaId = null;
        if ($documentPath && file_exists($documentPath) && $supportsDocumentHeader) {
            $uploadResult = $this->whatsappService->uploadMedia($setting, $documentPath, 'application/pdf');
            if ($uploadResult['success']) {
                $mediaId = $uploadResult['media_id'];
            } else {
                Log::warning("WhatsApp: Media upload failed | Template: {$templateName} | Phone: {$recipientPhone}");
            }
        } elseif ($documentPath && file_exists($documentPath) && !$supportsDocumentHeader) {
            Log::info("WhatsApp: Skipping document attachment because template does not support a document header | Template: {$templateName} | Type: {$notificationType}");
        }

        // Determine document filename based on notification type
        $documentFilename = null;
        if ($mediaId && $documentPath) {
            $filename = basename($documentPath);
            // If it's a sales report, use appropriate name
            if (in_array($notificationType, ['daily_sales_report', 'weekly_sales_report', 'monthly_sales_report'])) {
                $periodType = match($notificationType) {
                    'daily_sales_report' => 'Daily',
                    'weekly_sales_report' => 'Weekly',
                    'monthly_sales_report' => 'Monthly',
                    default => 'Sales',
                };
                $documentFilename = "{$periodType} Sales Report.pdf";
            } elseif ($notificationType === 'operations_summary') {
                $documentFilename = 'Daily Operations Summary.pdf';
            } else {
                $documentFilename = $filename;
            }
        }

        // Send message with or without document
        if ($mediaId) {
            $result = $this->whatsappService->sendTemplateMessageWithDocument(
                $setting,
                $recipientPhone,
                $templateName,
                $formattedVariables,
                $mediaId,
                $language,
                $documentFilename
            );
        } else {
            $result = $this->whatsappService->sendTemplateMessage(
                $setting,
                $recipientPhone,
                $templateName,
                $formattedVariables,
                $language
            );
        }

        // Update log and log result
        if ($result['success']) {
            $log->markAsSent($result['message_id'] ?? '');
            Log::info("WhatsApp: ✅ Sent | Template: {$templateName} | Type: {$notificationType} | Recipient: {$recipientType} | Phone: {$recipientPhone}");
        } else {
            $log->markAsFailed($result['error'] ?? 'Unknown error');
            Log::error("WhatsApp: ❌ Failed | Template: {$templateName} | Type: {$notificationType} | Phone: {$recipientPhone} | Error: " . ($result['error'] ?? 'Unknown error'));
        }

        return $result;
    }

    protected function templateSupportsDocumentHeader(string $notificationType): bool
    {
        $definition = WhatsAppTemplateDefinition::where('notification_type', $notificationType)
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            return false;
        }

        $components = $definition->getTemplateJsonArray()['components'] ?? [];

        foreach ($components as $component) {
            if (
                ($component['type'] ?? null) === 'HEADER' &&
                strtoupper((string) ($component['format'] ?? '')) === 'DOCUMENT'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if notification type is enabled for restaurant.
     *
     * @param int $restaurantId
     * @param string $notificationType
     * @param string $language
     * @return bool
     */
    public function isNotificationEnabled(
        int $restaurantId,
        string $notificationType,
        string $language = 'en'
    ): bool {
        $setting = WhatsAppSetting::where('restaurant_id', $restaurantId)
            ->where('is_enabled', true)
            ->first();

        if (!$setting || !$setting->isConfigured()) {
            return false;
        }

        $mapping = WhatsAppTemplateMapping::where('restaurant_id', $restaurantId)
            ->where('notification_type', $notificationType)
            ->where('language_code', $language)
            ->where('is_active', true)
            ->first();

        return $mapping !== null;
    }

    /**
     * Format variables for consolidated template based on notification type
     * Returns format: ['header' => [value], 'body' => [values...]]
     */
    protected function formatVariablesForConsolidatedTemplate(string $notificationType, array $variables, string $recipientType = ''): array
    {
        $consolidatedType = WhatsAppTemplateMapper::getConsolidatedTemplateName($notificationType);

        $formatted = match($consolidatedType) {
            'order_notifications' => WhatsAppTemplateMapper::formatOrderNotification($notificationType, $variables),
            'payment_notification' => WhatsAppTemplateMapper::formatPaymentNotification($notificationType, $variables),
            'reservation_notification' => WhatsAppTemplateMapper::formatReservationNotification($notificationType, $variables),
            'new_order_alert' => WhatsAppTemplateMapper::formatNewOrderAlert($recipientType ?: 'admin', $variables),
            'delivery_notification' => WhatsAppTemplateMapper::formatDeliveryNotification($notificationType, $variables),
            'kitchen_notification' => WhatsAppTemplateMapper::formatKitchenNotification($notificationType, $variables),
            'staff_notification' => WhatsAppTemplateMapper::formatStaffNotification($notificationType, $variables),
            'sales_report' => WhatsAppTemplateMapper::formatSalesReport($notificationType, $variables),
            'operations_summary' => WhatsAppTemplateMapper::formatOperationsSummary($variables),
            'inventory_alert' => WhatsAppTemplateMapper::formatInventoryAlert($variables),
            default => ['header' => [], 'body' => $variables], // Default format
        };
        
        // Ensure format is correct: ['header' => [...], 'body' => [...], 'buttons' => [...]]
        // The formatted result from mapper should already have 'body' and optionally 'buttons'
        // We just need to ensure 'header' exists
        
        // If the formatted result already has 'body' key (which should be an array of values)
        // and it's not nested, just add header if missing
        if (isset($formatted['body']) && is_array($formatted['body']) && !isset($formatted['body']['body'])) {
            // Body is already correct format (array of values)
            if (!isset($formatted['header'])) {
                $formatted['header'] = [];
            }
        }
        // If body is nested (has 'body' key inside), extract it
        elseif (isset($formatted['body']['body'])) {
            // Extract nested structure: body has {body: [...], buttons: [...]}
            $bodyValues = $formatted['body']['body'] ?? [];
            $buttons = $formatted['body']['buttons'] ?? $formatted['buttons'] ?? [];
            $formatted = [
                'header' => $formatted['header'] ?? [],
                'body' => $bodyValues,
            ];
            if (!empty($buttons)) {
                $formatted['buttons'] = $buttons;
            }
        }
        // If header is missing and body doesn't exist, wrap it
        elseif (!isset($formatted['header'])) {
            $formatted = ['header' => [], 'body' => $formatted];
        }
        
        return $formatted;
    }
}

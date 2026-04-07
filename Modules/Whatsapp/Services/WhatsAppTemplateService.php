<?php

namespace Modules\Whatsapp\Services;

use Modules\Whatsapp\Entities\WhatsAppTemplateDefinition;
use Modules\Whatsapp\Entities\WhatsAppTemplateMapping;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateService
{
    /**
     * Get template JSON for copy-paste to WhatsApp Portal.
     *
     * @param string $notificationType
     * @param string $language
     * @return array|null
     */
    public function getTemplateJson(string $notificationType, string $language = 'en'): ?array
    {
        $definition = WhatsAppTemplateDefinition::where('notification_type', $notificationType)
            ->where('is_active', true)
            ->first();

        if (!$definition) {
            return null;
        }

        $templateJson = $definition->getTemplateJsonArray();
        $templateJson['language'] = $language;

        return $templateJson;
    }

    /**
     * Get restaurant's template name for a notification type.
     * Falls back to template definition name if no mapping exists.
     *
     * @param int $restaurantId
     * @param string $notificationType
     * @param string $language
     * @return string|null
     */
    public function getRestaurantTemplateName(
        int $restaurantId,
        string $notificationType,
        string $language = 'en'
    ): ?string {
        $mapping = WhatsAppTemplateMapping::where('restaurant_id', $restaurantId)
            ->where('notification_type', $notificationType)
            ->where('language_code', $language)
            ->where('is_active', true)
            ->first();

        if ($mapping) {
            return $mapping->template_name;
        }

        // Fallback: Use template name from definition JSON if no mapping exists
        $definition = WhatsAppTemplateDefinition::where('notification_type', $notificationType)
            ->where('is_active', true)
            ->first();

        if ($definition) {
            // Get the actual template name from JSON (the 'name' field in template_json)
            $templateJson = $definition->getTemplateJsonArray();
            $templateName = $templateJson['name'] ?? $definition->template_name;
            
            // Log::info('WhatsApp Template Service: Using template definition as fallback (no mapping found)', [
            //     'restaurant_id' => $restaurantId,
            //     'notification_type' => $notificationType,
            //     'template_name' => $templateName,
            // ]);
            return $templateName;
        }

        // Log::warning('WhatsApp Template Service: No template mapping or definition found', [
        //     'restaurant_id' => $restaurantId,
        //     'notification_type' => $notificationType,
        //     'language' => $language,
        // ]);

        return null;
    }

    /**
     * Validate template name format (WhatsApp requirements).
     *
     * @param string $templateName
     * @return bool
     */
    public function validateTemplateName(string $templateName): bool
    {
        // WhatsApp template names must:
        // - Only contain lowercase letters, numbers, and underscores
        // - Not contain spaces or special characters
        // - Be between 1-512 characters
        return preg_match('/^[a-z0-9_]{1,512}$/', $templateName) === 1;
    }

    /**
     * Get all template definitions by category.
     *
     * @param string|null $category
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTemplateDefinitions(?string $category = null)
    {
        $query = WhatsAppTemplateDefinition::active();

        if ($category) {
            $query->byCategory($category);
        }

        return $query->orderBy('category')->orderBy('template_name')->get();
    }

    /**
     * Get template definition by notification type.
     *
     * @param string $notificationType
     * @return WhatsAppTemplateDefinition|null
     */
    public function getTemplateDefinition(string $notificationType): ?WhatsAppTemplateDefinition
    {
        return WhatsAppTemplateDefinition::where('notification_type', $notificationType)
            ->where('is_active', true)
            ->first();
    }
}


<?php

namespace Modules\Whatsapp\Livewire\Superadmin;

use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Modules\Whatsapp\Entities\WhatsAppSetting;
use Modules\Whatsapp\Services\WhatsAppService;
use Modules\Whatsapp\Services\WhatsAppTemplateService;

class WhatsAppSettings extends Component
{
    use LivewireAlert;

    public $wabaId;
    public $accessToken;
    public $phoneNumberId;
    public $verifyToken;
    public $isEnabled = false;
    
    public $templates = [];
    public $selectedTemplate = null;
    public $templateJson = null;
    public $templateDetails = null;
    public $selectedCategory = null;
    public $templatePreview = null;
    public $loadingPreview = false;
    public $previewError = null;
    
    protected $whatsappService;
    protected $templateService;
    
    /**
     * Fetch template preview from Meta API
     */
    public function fetchTemplatePreview($templateName)
    {
        $this->loadingPreview = true;
        $this->previewError = null;
        $this->templatePreview = null;
        
        try {
            $setting = WhatsAppSetting::whereNull('restaurant_id')->first();
            
            if (!$setting || !$setting->isConfigured()) {
                $this->previewError = 'WhatsApp is not configured. Please configure WhatsApp settings first.';
                $this->loadingPreview = false;
                Log::warning('WhatsApp Template Preview - Configuration Missing', [
                    'template_name' => $templateName,
                ]);
                return;
            }
            
            Log::info('WhatsApp Template Preview Requested', [
                'template_name' => $templateName,
                'waba_id' => $setting->waba_id,
            ]);
            
            $result = $this->whatsappService->getTemplateDetails($setting, $templateName);
            
            if ($result['success']) {
                $this->templatePreview = $result['data'];
                Log::info('WhatsApp Template Preview Loaded Successfully', [
                    'template_name' => $templateName,
                    'template_id' => $this->templatePreview['id'] ?? null,
                    'status' => $this->templatePreview['status'] ?? null,
                ]);
            } else {
                $this->previewError = $result['error'] ?? 'Failed to fetch template preview';
                Log::error('WhatsApp Template Preview Failed', [
                    'template_name' => $templateName,
                    'error' => $this->previewError,
                ]);
            }
        } catch (\Exception $e) {
            $this->previewError = 'Error fetching template: ' . $e->getMessage();
            Log::error('WhatsApp Template Preview Exception', [
                'template_name' => $templateName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->loadingPreview = false;
        }
    }
    
    /**
     * Close template preview
     */
    public function closePreview()
    {
        $this->templatePreview = null;
        $this->previewError = null;
    }

    public function boot(WhatsAppService $whatsappService, WhatsAppTemplateService $templateService)
    {
        $this->whatsappService = $whatsappService;
        $this->templateService = $templateService;
    }

    public function mount()
    {
        // Check if module is enabled
        if (!function_exists('module_enabled') || !module_enabled('Whatsapp')) {
            abort(404, 'WhatsApp module is not enabled');
        }

        // Get global setting (restaurant_id = null) for superadmin
        $setting = WhatsAppSetting::whereNull('restaurant_id')->first();
        
        if ($setting) {
            $this->wabaId = $setting->waba_id;
            $this->accessToken = $setting->access_token ?? '';
            $this->phoneNumberId = $setting->phone_number_id;
            $this->verifyToken = $setting->verify_token ?? '';
            $this->isEnabled = $setting->is_enabled;
        } else {
            $this->accessToken = '';
            $this->verifyToken = '';
        }
        
        // Load all template definitions
        $this->loadTemplates();
    }
    
    public function loadTemplates()
    {
        $this->templates = $this->templateService
            ->getTemplateDefinitions()
            ->reject(fn ($template) => $template->notification_type === 'low_inventory_alert')
            ->values();
    }

    public function updatedSelectedCategory()
    {
        // Reset selected template when category changes
        $this->selectedTemplate = null;
        $this->templateDetails = null;
        $this->templateJson = null;
        // Clear template preview when category changes
        $this->templatePreview = null;
        $this->previewError = null;
    }

    public function selectTemplate($notificationType)
    {
        $this->selectedTemplate = $notificationType;
        $templateJson = $this->templateService->getTemplateJson($notificationType);
        $this->templateJson = $templateJson ? json_encode($templateJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null;
        
        // Clear template preview when switching templates
        $this->templatePreview = null;
        $this->previewError = null;
        
        // Parse template details for display
        if ($templateJson) {
            $this->templateDetails = $this->parseTemplateDetails($templateJson, $notificationType);
        } else {
            $this->templateDetails = null;
        }

        $this->dispatch('scroll-to-template-guide');
    }

    /**
     * Parse template JSON to extract detailed information for display.
     */
    protected function parseTemplateDetails(array $templateJson, string $notificationType): array
    {
        $details = [
            'name' => $templateJson['name'] ?? $notificationType,
            'category' => $templateJson['category'] ?? 'UTILITY',
            'language' => $templateJson['language'] ?? 'en',
            'header' => null,
            'body' => null,
            'footer' => null,
            'buttons' => [],
            'variables' => [],
        ];

        if (!isset($templateJson['components'])) {
            return $details;
        }

        // Get sample variables from template definition first (needed for button URL variables)
        $definition = $this->templateService->getTemplateDefinition($notificationType);
        if ($definition && $definition->sample_variables) {
            $details['variables'] = $definition->sample_variables;
        }

        foreach ($templateJson['components'] as $component) {
            $type = $component['type'] ?? null;

            switch ($type) {
                case 'HEADER':
                    $details['header'] = [
                        'format' => $component['format'] ?? 'TEXT',
                        'text' => $component['text'] ?? null,
                        'example' => $component['example'] ?? null,
                    ];
                    break;

                case 'BODY':
                    $bodyText = $component['text'] ?? '';
                    $details['body'] = [
                        'text' => $bodyText,
                        'variables' => $this->extractVariables($bodyText),
                    ];
                    break;

                case 'FOOTER':
                    $details['footer'] = [
                        'text' => $component['text'] ?? null,
                    ];
                    break;

                case 'BUTTONS':
                    if (isset($component['buttons'])) {
                        $baseUrl = request()->getSchemeAndHttpHost();
                        // Get button URL variable descriptions from sample_variables
                        $buttonUrlVars = [];
                        if (isset($details['variables'])) {
                            foreach ($details['variables'] as $var) {
                                if (str_starts_with($var, 'Button URL:')) {
                                    // Extract variable number and description
                                    // Format: "Button URL: Description (for Button Name button)"
                                    $desc = preg_replace('/Button URL:\s*/', '', $var);
                                    $desc = preg_replace('/\s*\(for.*button\)/i', '', $desc);
                                    $buttonUrlVars[] = trim($desc);
                                }
                            }
                        }
                        
                        foreach ($component['buttons'] as $buttonIndex => $button) {
                            $url = $button['url'] ?? null;
                            $originalUrl = $url; // Keep original for variable extraction
                            // Replace yourdomain.com and example.com with current website URL
                            if ($url) {
                                $url = str_replace(['https://yourdomain.com', 'https://example.com'], $baseUrl, $url);
                            }
                            
                            $example = $button['example'][0] ?? null;
                            // Replace yourdomain.com and example.com in example URL too
                            if ($example) {
                                $example = str_replace(['https://yourdomain.com', 'https://example.com'], $baseUrl, $example);
                            }
                            
                            // Extract URL variables
                            $urlVariables = [];
                            if ($originalUrl) {
                                preg_match_all('/\{\{(\d+)\}\}/', $originalUrl, $matches);
                                if (!empty($matches[1])) {
                                    foreach ($matches[1] as $varIndex => $varNum) {
                                        $urlVariables[] = [
                                            'var' => '{{' . $varNum . '}}',
                                            'description' => $buttonUrlVars[$varIndex] ?? 'Variable ' . $varNum,
                                        ];
                                    }
                                }
                            }
                            
                            $details['buttons'][] = [
                                'type' => $button['type'] ?? null,
                                'text' => $button['text'] ?? null,
                                'url' => $url,
                                'original_url' => $originalUrl, // Keep original URL with {{1}} format
                                'phone_number' => $button['phone_number'] ?? null,
                                'example' => $example,
                                'url_variables' => $urlVariables,
                            ];
                        }
                    }
                    break;
            }
        }

        return $details;
    }

    /**
     * Extract variable placeholders from text ({{1}}, {{2}}, etc.).
     */
    protected function extractVariables(string $text): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        return $matches[1] ?? [];
    }

    public function copyTemplateJson()
    {
        $this->dispatch('copy-to-clipboard', content: $this->templateJson);
        $this->alert('success', __('whatsapp::app.templateCopied'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function testConnection()
    {
        $this->validate([
            'wabaId' => 'required',
            'accessToken' => 'required',
            'phoneNumberId' => 'required',
        ]);

        // Get or create temporary setting for testing
        $setting = WhatsAppSetting::firstOrNew(['restaurant_id' => null]);
        
        // Set values for testing
        $setting->waba_id = $this->wabaId;
        $setting->phone_number_id = $this->phoneNumberId;
        $setting->access_token = $this->accessToken;

        $result = $this->whatsappService->testConnection($setting);

        if ($result['success']) {
            $message = $result['message'] ?? __('whatsapp::app.connectionSuccessful');
            
            // Add phone number details if available
            if (isset($result['data']['verified_name'])) {
                $message .= ' Phone: ' . ($result['data']['display_phone_number'] ?? 'N/A') . ' (' . ($result['data']['verified_name'] ?? 'N/A') . ')';
            }
            
            $this->alert('success', $message, [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 5000,
            ]);
        } else {
            $errorMessage = $result['error'] ?? __('app.unknownError');
            $this->alert('error', __('whatsapp::app.connectionFailed') . ': ' . $errorMessage, [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 8000,
            ]);
        }
    }

    public function submitForm()
    {
        $this->validate([
            'wabaId' => 'required_if:isEnabled,true',
            'accessToken' => 'required_if:isEnabled,true',
            'phoneNumberId' => 'required_if:isEnabled,true',
            'verifyToken' => 'nullable|string|max:255',
        ]);

        // Get or create global setting (restaurant_id = null for superadmin)
        $setting = WhatsAppSetting::firstOrNew(['restaurant_id' => null]);

        $setting->fill([
            'waba_id' => $this->wabaId,
            'access_token' => $this->accessToken,
            'phone_number_id' => $this->phoneNumberId,
            'verify_token' => $this->verifyToken,
            'is_enabled' => $this->isEnabled,
            'restaurant_id' => null, // Global setting for superadmin
        ]);

        $setting->save();

        // Reload settings with actual values (will be hidden by password type)
        $this->wabaId = $setting->waba_id;
        $this->accessToken = $setting->access_token ?? '';
        $this->phoneNumberId = $setting->phone_number_id;
        $this->verifyToken = $setting->verify_token ?? '';
        $this->isEnabled = $setting->is_enabled;

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('whatsapp::livewire.superadmin.whatsapp-settings');
    }
}

<?php

namespace Modules\Whatsapp\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Entities\WhatsAppSetting;

class WhatsAppService
{
    protected Client $client;
    protected string $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('whatsapp.api_base_url', 'https://graph.facebook.com/v18.0');
        $this->client = new Client([
            'timeout' => config('whatsapp.api_timeout', 30),
        ]);
    }

    /**
     * Send WhatsApp message using template.
     *
     * @param WhatsAppSetting $setting
     * @param string $to Phone number (can include + sign, will be removed automatically)
     * @param string $templateName Template name
     * @param array $variables Template variables
     * @param string $language Language code (default: en)
     * @return array
     */
    public function sendTemplateMessage(
        WhatsAppSetting $setting,
        string $to,
        string $templateName,
        array $variables = [],
        string $language = 'en'
    ): array {
        $formattedPhone = $this->formatPhoneNumber($to);
        $url = "{$this->apiBaseUrl}/{$setting->phone_number_id}/messages";
        
        // Log removed - will log final result only

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $formattedPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language,
                    ],
                ],
            ];

            // Add template parameters if variables provided
            // Variables format: ['header' => [value], 'body' => [values...], 'buttons' => [[index => value]]]
            if (!empty($variables)) {
                $components = [];
                
                // Handle header variables (if provided)
                if (isset($variables['header']) && !empty($variables['header'])) {
                    $headerParams = [];
                    foreach ($variables['header'] as $value) {
                        $headerParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($headerParams)) {
                        $components[] = [
                            'type' => 'header',
                            'parameters' => $headerParams,
                        ];
                    }
                }
                
                // Handle body variables (if provided)
                if (isset($variables['body']) && !empty($variables['body'])) {
                    $bodyParams = [];
                    foreach ($variables['body'] as $value) {
                        $bodyParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($bodyParams)) {
                        $components[] = [
                            'type' => 'body',
                            'parameters' => $bodyParams,
                        ];
                    }
                } elseif (!isset($variables['header'])) {
                    // Backward compatibility: if variables is a simple array, treat as body
                    $bodyParams = [];
                    foreach ($variables as $value) {
                        $bodyParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($bodyParams)) {
                        $components[] = [
                            'type' => 'body',
                            'parameters' => $bodyParams,
                        ];
                    }
                }

                if (isset($variables['footer']) && !empty($variables['footer'])) {
                    $footerParams = [];
                    foreach ($variables['footer'] as $value) {
                        $footerParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($footerParams)) {
                        $components[] = [
                            'type' => 'footer',
                            'parameters' => $footerParams,
                        ];
                    }
                }

                // Handle button variables (if provided)
                // Format: ['buttons' => [['index' => 0, 'sub_type' => 'url', 'parameters' => [['type' => 'text', 'text' => 'value']]]]]
                if (isset($variables['buttons']) && !empty($variables['buttons'])) {
                    foreach ($variables['buttons'] as $button) {
                        if (isset($button['index']) && isset($button['sub_type']) && isset($button['parameters'])) {
                            $components[] = [
                                'type' => 'button',
                                'sub_type' => $button['sub_type'],
                                'index' => (string) $button['index'],
                                'parameters' => $button['parameters'],
                            ];
                        }
                    }
                }

                if (!empty($components)) {
                    $payload['template']['components'] = $components;
                }
            }

            // Log removed - will log final result only

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$setting->access_token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $messageId = $responseData['messages'][0]['id'] ?? null;
            $messageStatus = $responseData['messages'][0]['message_status'] ?? null;
            $contacts = $responseData['contacts'] ?? [];
            $waId = $contacts[0]['wa_id'] ?? null;

            // Check if wa_id matches the input phone number (indicates number is registered on WhatsApp)
            $isRegistered = ($waId === $formattedPhone);
            
            Log::info('WhatsApp Service: API request successful', [
                'template_name' => $templateName,
                'to' => $formattedPhone,
                'message_id' => $messageId,
                'message_status' => $messageStatus,
                'wa_id' => $waId,
                'is_registered_on_whatsapp' => $isRegistered,
                'response_status' => $response->getStatusCode(),
                'response_data' => $responseData,
                'note' => $messageStatus === 'accepted' ? 
                    ($isRegistered ? 
                        'Message accepted by API. Status "accepted" means message is queued. Check webhook for delivery status. If recipient did not receive message, verify: 1) Phone number is in Meta Business Manager allowed recipient list (for test accounts), 2) Webhook is configured to track delivery status.' :
                        'Message accepted by API but wa_id does not match input phone number. This may indicate the phone number is not registered on WhatsApp. Verify the phone number is active on WhatsApp and try again.') :
                    null,
            ]);

            return [
                'success' => true,
                'message_id' => $messageId,
                'message_status' => $messageStatus,
                'wa_id' => $waId,
                'data' => $responseData,
            ];
        } catch (RequestException $e) {
            $responseBody = null;
            $statusCode = null;
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();
            }
            
            // Error logged by WhatsAppNotificationService

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            // Error logged by WhatsAppNotificationService

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload media file to WhatsApp and get media ID.
     *
     * @param WhatsAppSetting $setting
     * @param string $filePath Full path to the file
     * @param string $mimeType MIME type (e.g., 'application/pdf')
     * @return array
     */
    public function uploadMedia(WhatsAppSetting $setting, string $filePath, string $mimeType = 'application/pdf'): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }

            $url = "{$this->apiBaseUrl}/{$setting->phone_number_id}/media";
            
            Log::info('WhatsApp Service: Uploading media', [
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'file_size' => filesize($filePath),
            ]);

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$setting->access_token}",
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                    ],
                    [
                        'name' => 'type',
                        'contents' => $mimeType,
                    ],
                    [
                        'name' => 'messaging_product',
                        'contents' => 'whatsapp',
                    ],
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $mediaId = $responseData['id'] ?? null;

            Log::info('WhatsApp Service: Media uploaded successfully', [
                'media_id' => $mediaId,
                'response' => $responseData,
            ]);

            return [
                'success' => true,
                'media_id' => $mediaId,
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp Service: Media upload failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send WhatsApp template message with document media.
     *
     * @param WhatsAppSetting $setting
     * @param string $to Phone number
     * @param string $templateName Template name
     * @param array $variables Template variables
     * @param string|null $mediaId Media ID from uploadMedia()
     * @param string $language Language code (default: en)
     * @return array
     */
    public function sendTemplateMessageWithDocument(
        WhatsAppSetting $setting,
        string $to,
        string $templateName,
        array $variables = [],
        ?string $mediaId = null,
        string $language = 'en',
        ?string $documentFilename = null
    ): array {
        $formattedPhone = $this->formatPhoneNumber($to);
        $url = "{$this->apiBaseUrl}/{$setting->phone_number_id}/messages";
        
        Log::info('WhatsApp Service: Preparing to send template message with document', [
            'template_name' => $templateName,
            'to' => $formattedPhone,
            'media_id' => $mediaId,
        ]);

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $formattedPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $language,
                    ],
                ],
            ];

            // Add template parameters if variables provided
            if (!empty($variables)) {
                $components = [];
                
                // Handle header variables (if provided)
                if (isset($variables['header']) && !empty($variables['header'])) {
                    $headerParams = [];
                    foreach ($variables['header'] as $value) {
                        $headerParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($headerParams)) {
                        $components[] = [
                            'type' => 'header',
                            'parameters' => $headerParams,
                        ];
                    }
                }
                
                // Handle body variables (if provided)
                if (isset($variables['body']) && !empty($variables['body'])) {
                    $bodyParams = [];
                    foreach ($variables['body'] as $value) {
                        $bodyParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($bodyParams)) {
                        $components[] = [
                            'type' => 'body',
                            'parameters' => $bodyParams,
                        ];
                    }
                } elseif (!isset($variables['header'])) {
                    // Backward compatibility: if variables is a simple array, treat as body
                    $bodyParams = [];
                    foreach ($variables as $value) {
                        $bodyParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($bodyParams)) {
                        $components[] = [
                            'type' => 'body',
                            'parameters' => $bodyParams,
                        ];
                    }
                }

                if (isset($variables['footer']) && !empty($variables['footer'])) {
                    $footerParams = [];
                    foreach ($variables['footer'] as $value) {
                        $footerParams[] = [
                            'type' => 'text',
                            'text' => (string) $value,
                        ];
                    }
                    if (!empty($footerParams)) {
                        $components[] = [
                            'type' => 'footer',
                            'parameters' => $footerParams,
                        ];
                    }
                }

                // Handle button variables (if provided)
                if (isset($variables['buttons']) && !empty($variables['buttons'])) {
                    foreach ($variables['buttons'] as $button) {
                        if (isset($button['index']) && isset($button['sub_type']) && isset($button['parameters'])) {
                            $components[] = [
                                'type' => 'button',
                                'sub_type' => $button['sub_type'],
                                'index' => (string) $button['index'],
                                'parameters' => $button['parameters'],
                            ];
                        }
                    }
                }

                // Add document media to header if media ID provided
                if ($mediaId) {
                    // Find or create header component for document
                    $headerComponent = null;
                    foreach ($components as $index => $component) {
                        if ($component['type'] === 'header') {
                            $headerComponent = &$components[$index];
                            break;
                        }
                    }
                    
                    if (!$headerComponent) {
                        $components[] = [
                            'type' => 'header',
                            'parameters' => [],
                        ];
                        $headerComponent = &$components[count($components) - 1];
                    }
                    
                    // Add document parameter to header
                    $filename = $documentFilename ?? 'Document.pdf';
                    $headerComponent['parameters'][] = [
                        'type' => 'document',
                        'document' => [
                            'id' => $mediaId,
                            'filename' => $filename,
                        ],
                    ];
                }

                if (!empty($components)) {
                    $payload['template']['components'] = $components;
                }
            } elseif ($mediaId) {
                // If no variables but media ID provided, add document to header
                $filename = $documentFilename ?? 'Document.pdf';
                $payload['template']['components'] = [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'id' => $mediaId,
                                    'filename' => $filename,
                                ],
                            ],
                        ],
                    ],
                ];
            }

            Log::info('WhatsApp Service: Sending API request with document', [
                'url' => $url,
                'template_name' => $templateName,
                'to' => $formattedPhone,
                'media_id' => $mediaId,
                'payload' => $payload,
            ]);

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$setting->access_token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            $messageId = $responseData['messages'][0]['id'] ?? null;
            $messageStatus = $responseData['messages'][0]['message_status'] ?? null;

            Log::info('WhatsApp Service: API request successful (with document)', [
                'template_name' => $templateName,
                'to' => $formattedPhone,
                'message_id' => $messageId,
                'message_status' => $messageStatus,
            ]);

            return [
                'success' => true,
                'message_id' => $messageId,
                'message_status' => $messageStatus,
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            // Error logged by WhatsAppNotificationService

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test WhatsApp API connection.
     * 
     * This method tests the connection by:
     * 1. Making a GET request to the phone number ID endpoint
     * 2. Verifying the access token is valid
     * 3. Checking if the phone number ID exists and is accessible
     * 4. Returns phone number details if successful
     *
     * @param WhatsAppSetting $setting
     * @return array
     */
    public function testConnection(WhatsAppSetting $setting): array
    {
        try {
            // Test endpoint: GET /{phone-number-id}
            // This endpoint verifies:
            // - Access token is valid
            // - Phone number ID exists
            // - User has permission to access this phone number
            $url = "{$this->apiBaseUrl}/{$setting->phone_number_id}";

            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$setting->access_token}",
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Check if we got valid response with phone number data
            if (isset($responseData['id']) && isset($responseData['verified_name'])) {
                return [
                    'success' => true,
                    'message' => 'Connection successful! Phone number verified.',
                    'data' => [
                        'phone_number_id' => $responseData['id'] ?? null,
                        'display_phone_number' => $responseData['display_phone_number'] ?? null,
                        'verified_name' => $responseData['verified_name'] ?? null,
                        'quality_rating' => $responseData['quality_rating'] ?? null,
                    ],
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful!',
                'data' => $responseData,
            ];
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            $statusCode = $e->getCode();
            
            // Try to get more detailed error from response
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                
                if (isset($errorData['error'])) {
                    $errorMessage = $errorData['error']['message'] ?? $errorMessage;
                    $statusCode = $errorData['error']['code'] ?? $statusCode;
                }
            }

            // Provide user-friendly error messages
            $userFriendlyMessage = $this->getUserFriendlyErrorMessage($statusCode, $errorMessage);

            return [
                'success' => false,
                'error' => $userFriendlyMessage,
                'error_code' => $statusCode,
                'raw_error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Get template details from Meta API
     * 
     * @param WhatsAppSetting $setting
     * @param string $templateName Template name to fetch
     * @return array
     */
    public function getTemplateDetails(WhatsAppSetting $setting, string $templateName): array
    {
        try {
            // Endpoint: GET /{whatsapp-business-account-id}/message_templates
            // Filter by name to get specific template
            $url = "{$this->apiBaseUrl}/{$setting->waba_id}/message_templates";
            
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$setting->access_token}",
                ],
                'query' => [
                    'name' => $templateName,
                    'limit' => 1,
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['data']) && !empty($responseData['data'])) {
                $template = $responseData['data'][0];
                
                // Log successful template fetch
                Log::info('WhatsApp Template Fetched Successfully', [
                    'template_name' => $templateName,
                    'template_id' => $template['id'] ?? null,
                    'status' => $template['status'] ?? null,
                    'category' => $template['category'] ?? null,
                    'language' => $template['language'] ?? null,
                    'waba_id' => $setting->waba_id,
                ]);
                
                return [
                    'success' => true,
                    'data' => $template,
                    'message' => 'Template found successfully',
                ];
            }
            
            // Log when template not found
            Log::warning('WhatsApp Template Not Found', [
                'template_name' => $templateName,
                'waba_id' => $setting->waba_id,
            ]);

            return [
                'success' => false,
                'error' => 'Template not found',
                'message' => "Template '{$templateName}' not found in your WhatsApp Business Account",
            ];
        } catch (RequestException $e) {
            $responseBody = null;
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
            }
            
            Log::error('WhatsApp API Error - Get Template', [
                'error' => $e->getMessage(),
                'response' => $responseBody,
                'template_name' => $templateName,
                'waba_id' => $setting->waba_id,
                'error_code' => $e->getCode(),
            ]);

            $errorData = json_decode($responseBody, true);
            $errorMessage = $errorData['error']['message'] ?? $e->getMessage();

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $e->getCode(),
                'error_data' => $errorData['error'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Error - Get Template', [
                'error' => $e->getMessage(),
                'template_name' => $templateName,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user-friendly error message based on error code.
     *
     * @param int $statusCode
     * @param string $errorMessage
     * @return string
     */
    protected function getUserFriendlyErrorMessage(int $statusCode, string $errorMessage): string
    {
        return match ($statusCode) {
            400 => 'Invalid request. Please check your Phone Number ID.',
            401 => 'Invalid Access Token. Please verify your access token is correct and not expired.',
            403 => 'Access denied. Please check your access token permissions.',
            404 => 'Phone Number ID not found. Please verify your Phone Number ID is correct.',
            429 => 'Rate limit exceeded. Please try again later.',
            default => "Connection failed: {$errorMessage}",
        };
    }

    /**
     * Format phone number for WhatsApp API.
     * 
     * WhatsApp API requires phone numbers in E.164 format WITHOUT the + sign.
     * Example: +1234567890 should become 1234567890
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters (including +, spaces, dashes, etc.)
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Remove leading 0 if present (common in some countries)
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        // Return cleaned number (digits only, no + sign)
        // WhatsApp API expects: 1234567890 (not +1234567890)
        return $phoneNumber;
    }
}

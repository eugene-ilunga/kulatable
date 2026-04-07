<?php

namespace Modules\Aitools\Livewire\SuperAdmin;

use Modules\Aitools\Entities\AiToolsGlobalSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class Setting extends Component
{
    use LivewireAlert;

    public $settings;
    public $openaiApiKey = '';
    public $openaiOrganizationId = '';
    public $testStatus = null; // 'testing', 'success', 'error'
    public $testMessage = '';
    public $apiKeyDetails = null;
    public $loadingDetails = false;
    public $totalTokensConsumed = 0;
    public $totalTokensLimit = 0;
    public $loadingTokenStatistics = false;
    public $activeTab = 'settings'; // 'settings' or 'tokens'
    public $restaurants = [];
    public $selectedRestaurantId = null;
    public $tokenHistory = [];
    public $showHistoryModal = false;

    public function mount()
    {
        $this->settings = AiToolsGlobalSetting::firstOrCreate([]);
        $this->openaiApiKey = $this->settings->openai_api_key ?? '';
        $this->openaiOrganizationId = $this->settings->openai_organization_id ?? '';
        // Don't load token statistics on mount - will load via AJAX after page loads
        $this->loadRestaurants();
        $this->fetchApiKeyDetails();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        if ($tab === 'tokens') {
            $this->loadRestaurants();
        }
    }

    public function loadRestaurants()
    {
        $this->restaurants = \App\Models\Restaurant::with('package')
            ->where('ai_enabled', true)
            ->orderBy('name')
            ->get()
            ->map(function($restaurant) {
                $currentMonth = now()->format('Y-m');
                $tokensUsed = $restaurant->ai_monthly_tokens_used ?? 0;
                $tokenLimit = $restaurant->package->ai_monthly_token_limit ?? -1;

                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'package_name' => $restaurant->package->package_name ?? 'N/A',
                    'tokens_used' => $tokensUsed,
                    'token_limit' => $tokenLimit,
                    'unlimited' => $tokenLimit == -1,
                    'remaining' => $tokenLimit == -1 ? 999999 : max(0, $tokenLimit - $tokensUsed),
                ];
            })
            ->toArray();
    }

    public function showHistory($restaurantId)
    {
        $this->selectedRestaurantId = $restaurantId;
        $this->loadTokenHistory($restaurantId);
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->selectedRestaurantId = null;
        $this->tokenHistory = [];
    }

    public function loadTokenHistory($restaurantId)
    {
        $restaurant = \App\Models\Restaurant::with('package')->find($restaurantId);
        if (!$restaurant) {
            return;
        }

        // Get current month usage
        $currentMonth = now()->format('Y-m');
        $currentTokens = $restaurant->ai_monthly_tokens_used ?? 0;
        $currentLimit = $restaurant->package->ai_monthly_token_limit ?? -1;

        // Get historical data from database
        $history = DB::table('ai_token_usage_history')
            ->where('restaurant_id', $restaurantId)
            ->orderBy('month', 'desc')
            ->get()
            ->map(function($record) {
                return [
                    'month' => $record->month,
                    'tokens_used' => $record->tokens_used,
                    'token_limit' => $record->token_limit,
                    'unlimited' => $record->token_limit == -1,
                ];
            })
            ->toArray();

        // Add current month if not in history
        $hasCurrentMonth = collect($history)->contains(function($item) use ($currentMonth) {
            return $item['month'] === $currentMonth;
        });

        if (!$hasCurrentMonth && $currentTokens > 0) {
            array_unshift($history, [
                'month' => $currentMonth,
                'tokens_used' => $currentTokens,
                'token_limit' => $currentLimit,
                'unlimited' => $currentLimit == -1,
            ]);
        }

        $this->tokenHistory = $history;
    }

    public function loadTokenStatistics()
    {
        $this->loadingTokenStatistics = true;

        try {
            // Calculate total tokens consumed across all restaurants
            $this->totalTokensConsumed = \App\Models\Restaurant::whereNotNull('ai_monthly_tokens_used')
                ->sum('ai_monthly_tokens_used') ?? 0;

            // Calculate total token limit across all restaurants (sum of package limits)
            $this->totalTokensLimit = \App\Models\Restaurant::whereHas('package', function($query) {
                    $query->whereNotNull('ai_monthly_token_limit')
                          ->where('ai_monthly_token_limit', '!=', -1);
                })
                ->with('package')
                ->get()
                ->sum(function($restaurant) {
                    return $restaurant->package->ai_monthly_token_limit ?? 0;
                });
        } catch (\Exception $e) {
            // Handle error silently or log it
        } finally {
            $this->loadingTokenStatistics = false;
        }
    }

    public function updatedOpenaiApiKey()
    {
        // Reset test status when API key is changed
        $this->testStatus = null;
        $this->testMessage = '';
        $this->apiKeyDetails = null;
    }

    public function updatedOpenaiOrganizationId()
    {
        // Reset details when organization ID is changed
        $this->apiKeyDetails = null;
    }

    /**
     * Get headers for OpenAI API requests including organization if set
     */
    private function getOpenAIHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
        ];

        if (!empty($this->openaiOrganizationId)) {
            $headers['OpenAI-Organization'] = $this->openaiOrganizationId;
        }

        return $headers;
    }

    public function testApiKey()
    {
        $this->validate([
            'openaiApiKey' => 'required|string|max:500',
        ], [
            'openaiApiKey.required' => __('aitools::app.superadmin.apiKeyRequiredForTest'),
        ]);

        $this->testStatus = 'testing';
        $this->testMessage = '';

        try {
            // Make a simple API call to test the key
            $response = Http::withHeaders(array_merge($this->getOpenAIHeaders(), [
                'Content-Type' => 'application/json',
            ]))->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4.1-nano',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'test'
                    ]
                ],
                'max_tokens' => 5,
            ]);

            if ($response->successful()) {
                $this->testStatus = 'success';
                $this->testMessage = __('aitools::app.superadmin.apiKeyTestSuccess');
                $this->alert('success', __('aitools::app.superadmin.apiKeyTestSuccess'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
            } else {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $response->body();
                $this->testStatus = 'error';
                $this->testMessage = __('aitools::app.superadmin.apiKeyTestError') . ': ' . $errorMessage;
                $this->alert('error', __('aitools::app.superadmin.apiKeyTestError') . ': ' . $errorMessage, [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
            }
        } catch (\Exception $e) {
            $this->testStatus = 'error';
            $this->testMessage = __('aitools::app.superadmin.apiKeyTestError') . ': ' . $e->getMessage();
            $this->alert('error', __('aitools::app.superadmin.apiKeyTestError') . ': ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    public function fetchApiKeyDetails()
    {
        if (empty($this->openaiApiKey)) {
            $this->apiKeyDetails = null;
            $this->loadingDetails = false;
            return;
        }

        $this->loadingDetails = true;
        $details = [
            'organization' => null,
            'account' => null,
            'usage' => null,
            'tokenStats' => null,
            'errors' => [],
            'debug' => [],
        ];

        try {
            // Models fetching removed - not needed for API key details

            // Fetch organization information
            // Note: This endpoint may return 403 if the API key doesn't have organization access
            try {
                $orgResponse = Http::withHeaders($this->getOpenAIHeaders())
                    ->timeout(15)->get('https://api.openai.com/v1/organizations');

                if ($orgResponse->successful()) {
                    $orgData = $orgResponse->json();
                    if (isset($orgData['data']) && is_array($orgData['data']) && count($orgData['data']) > 0) {
                        $details['organization'] = $orgData['data'][0];
                    }
                } else {
                    // 403 is common for organization endpoint - not all API keys have access
                    if ($orgResponse->status() == 403) {
                        $details['debug']['organization'] = 'Organization endpoint requires special permissions (403)';
                    } else {
                        $details['errors']['organization'] = 'Failed to fetch organization: ' . $orgResponse->status();
                    }
                }
            } catch (\Exception $e) {
                $details['debug']['organization'] = 'Organization endpoint not accessible: ' . $e->getMessage();
            }

            // Fetch account information (user info) - this endpoint might not exist
            try {
                $userResponse = Http::withHeaders($this->getOpenAIHeaders())
                    ->timeout(15)->get('https://api.openai.com/v1/users/me');

                if ($userResponse->successful()) {
                    $details['account'] = $userResponse->json();
                } else {
                    // This endpoint might not exist, so we don't treat it as an error
                    $details['debug']['user_endpoint'] = 'Not available (status: ' . $userResponse->status() . ')';
                }
            } catch (\Exception $e) {
                $details['debug']['user_endpoint'] = 'Not available: ' . $e->getMessage();
            }

            // Try to get usage information
            // Note: OpenAI Usage API requires a 'date' parameter (YYYY-MM-DD format)
            // We only fetch today's usage to avoid rate limiting (5 requests per minute limit)
            try {
                // Use organization ID from settings if provided, otherwise try from API response
                $orgId = $this->openaiOrganizationId ?: ($details['organization']['id'] ?? null);
                $usageUrl = 'https://api.openai.com/v1/usage';

                // Try fetching usage for today first to test the endpoint
                $today = now()->format('Y-m-d');
                $usageParams = [
                    'date' => $today,
                ];

                if ($orgId) {
                    $usageParams['organization_id'] = $orgId;
                }

                $usageResponse = Http::withHeaders($this->getOpenAIHeaders())
                    ->timeout(15)->get($usageUrl, $usageParams);

                if ($usageResponse->successful()) {
                    $usageData = $usageResponse->json();
                    $details['usage'] = $usageData;
                    $details['debug']['usage_response'] = 'Success for ' . $today;

                    // Calculate token statistics
                    if (isset($usageData['data']) && is_array($usageData['data']) && count($usageData['data']) > 0) {
                        $totalTokens = 0;
                        $totalRequests = 0;
                        $totalPromptTokens = 0;
                        $totalCompletionTokens = 0;

                        foreach ($usageData['data'] as $day) {
                            if (isset($day['n_requests'])) {
                                $totalRequests += (int)$day['n_requests'];
                            }
                            if (isset($day['n_context_tokens_used'])) {
                                $totalPromptTokens += (int)$day['n_context_tokens_used'];
                            }
                            if (isset($day['n_generated_tokens_used'])) {
                                $totalCompletionTokens += (int)$day['n_generated_tokens_used'];
                            }
                        }

                        $totalTokens = $totalPromptTokens + $totalCompletionTokens;

                        // Show stats even if zero, but indicate it's for today only
                        $details['tokenStats'] = [
                            'total_tokens' => $totalTokens,
                            'total_requests' => $totalRequests,
                            'prompt_tokens' => $totalPromptTokens,
                            'completion_tokens' => $totalCompletionTokens,
                            'days_with_usage' => count($usageData['data']),
                            'is_today_only' => true,
                        ];

                        $details['debug']['usage_data_count'] = count($usageData['data']) . ' records';
                    } else {
                        // API succeeded but no data (likely no usage today)
                        $details['tokenStats'] = [
                            'total_tokens' => 0,
                            'total_requests' => 0,
                            'prompt_tokens' => 0,
                            'completion_tokens' => 0,
                            'days_with_usage' => 0,
                            'is_today_only' => true,
                            'no_data' => true,
                        ];
                        $details['debug']['usage_data'] = 'API call successful but no usage data for today';
                    }
                } else {
                    $errorBody = $usageResponse->json();
                    $errorMsg = $errorBody['error']['message'] ?? $usageResponse->body();
                    $statusCode = $usageResponse->status();

                    // Handle different error cases
                    if ($statusCode == 401 || $statusCode == 403) {
                        $details['debug']['usage'] = 'Usage API requires special permissions or organization access (Status: ' . $statusCode . ')';
                    } elseif ($statusCode == 404) {
                        $details['debug']['usage'] = 'Usage API endpoint not available for this API key (Status: 404)';
                    } else {
                        $details['errors']['usage'] = 'Usage API error (' . $statusCode . '): ' . $errorMsg;
                    }
                    $details['debug']['usage_error_details'] = 'Response: ' . substr($usageResponse->body(), 0, 200);
                }
            } catch (\Exception $e) {
                $details['debug']['usage'] = 'Usage API not accessible: ' . $e->getMessage();
            }

            // Try alternative: Check if we can get billing/subscription info
            // This endpoint might not exist, but worth trying
            try {
                $billingResponse = Http::withHeaders($this->getOpenAIHeaders())
                    ->timeout(10)->get('https://api.openai.com/v1/dashboard/billing/subscription');

                if ($billingResponse->successful()) {
                    $details['billing'] = $billingResponse->json();
                    $details['debug']['billing'] = 'Billing info available';
                }
            } catch (\Exception $e) {
                // Billing endpoint likely doesn't exist, ignore
            }

            $this->apiKeyDetails = $details;
        } catch (\Exception $e) {
            $details['error'] = 'General error: ' . $e->getMessage();
            $this->apiKeyDetails = $details;
        } finally {
            $this->loadingDetails = false;
        }
    }

    public function save()
    {
        $this->validate([
            'openaiApiKey' => 'nullable|string|max:500',
            'openaiOrganizationId' => 'nullable|string|max:100',
        ]);

        $this->settings->openai_api_key = $this->openaiApiKey ?: null;
        $this->settings->openai_organization_id = $this->openaiOrganizationId ?: null;
        $this->settings->save();

        // Fetch API key details after saving
        if (!empty($this->openaiApiKey)) {
            $this->fetchApiKeyDetails();
        } else {
            $this->apiKeyDetails = null;
            $this->loadingDetails = false;
        }

        $this->alert('success', __('aitools::app.messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('aitools::livewire.super-admin.setting');
    }
}

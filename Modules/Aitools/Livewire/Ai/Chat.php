<?php

namespace Modules\Aitools\Livewire\Ai;

use Modules\Aitools\Entities\AiConversation;
use Modules\Aitools\Services\Ai\AiOrchestrator;
use Modules\Aitools\Services\Ai\OpenAIClient;
use Modules\Aitools\Services\Ai\ToolRegistry;
use Modules\Aitools\Services\Ai\AiLogger;
use Modules\Aitools\Services\Ai\AiPolicy;
use Modules\Aitools\Services\Ai\SystemPrompt;
use Modules\Aitools\Services\Ai\AiToolServiceProvider;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Chat extends Component
{
    use LivewireAlert;

    public $conversationId = null;
    public $message = '';
    public $messages = [];
    public $conversations = [];
    public $isLoading = false;
    public $remainingTokens = 0;
    public $startTime = null;
    public $showCapabilitiesModal = false;

    private ?AiOrchestrator $orchestrator = null;

    public $accessDenied = false;
    public $accessDeniedReason = '';

    public function mount($conversationId = null)
    {
        // Check access first
        $policy = new AiPolicy();
        $restaurant = restaurant();
        $currentUser = user();

        if (!$restaurant || !$currentUser) {
            $this->accessDenied = true;
            $this->accessDeniedReason = 'Restaurant or user not found';
            return;
        }

        $accessCheck = $policy->canAccess($currentUser, $restaurant);
        if (!$accessCheck['allowed']) {
            $this->accessDenied = true;
            $this->accessDeniedReason = $accessCheck['reason'] ?? 'Access denied';
            return;
        }

        $this->remainingTokens = $accessCheck['remaining'] ?? 0;
        $this->loadConversations();

        if ($conversationId) {
            $this->conversationId = $conversationId;
            $this->loadConversation();
        }
    }

    public function loadConversations()
    {
        try {
            $restaurant = restaurant();
            $currentUser = user();

            if (!$restaurant || !$currentUser) {
                $this->conversations = collect([]);
                return;
            }

            $this->conversations = \Modules\Aitools\Entities\AiConversation::where('restaurant_id', $restaurant->id)
                ->where('user_id', $currentUser->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            $this->conversations = collect([]);
        }
    }

    public function loadConversation()
    {
        if (!$this->conversationId) {
            return;
        }

        $conversation = \Modules\Aitools\Entities\AiConversation::where('id', $this->conversationId)
            ->where('restaurant_id', restaurant()->id)
            ->where('user_id', user()->id)
            ->first();

        if (!$conversation) {
            $this->alert('error', 'Conversation not found');
            return;
        }

        $dbMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get();



        $this->messages = $dbMessages->map(function ($msg) {
            // User messages should be simple strings
            if ($msg->role === 'user') {
                $content = is_array($msg->content)
                    ? ($msg->content['text'] ?? json_encode($msg->content))
                    : $msg->content;
            } else {
                // Assistant messages should be arrays (with answer, widgets, followups)
                $content = $msg->content;
                if (is_string($content)) {
                    $decoded = json_decode($content, true);
                    $content = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['answer' => $content];
                }
            }

            return [
                'role' => $msg->role,
                'content' => $content,
                'created_at' => $msg->created_at->format('H:i'),
            ];
        })
            ->toArray();



        // Dispatch event for auto-scroll when conversation is loaded
        $this->dispatch('messages-updated');
    }

    public function selectConversation($id)
    {
        try {
            $this->conversationId = $id;
            $this->loadConversation();
        } catch (\Exception $e) {
            $this->alert('error', 'Error loading conversation: ' . $e->getMessage());
        }
    }

    public function newConversation()
    {
        try {
            $this->conversationId = null;
            $this->messages = [];
            $this->message = '';
            $this->loadConversations();

            $this->dispatch('conversation-cleared');
        } catch (\Exception $e) {
            $this->alert('error', 'Error: ' . $e->getMessage());
        }
    }

    public function showCapabilities()
    {
        $this->showCapabilitiesModal = true;
    }

    public function closeCapabilities()
    {
        $this->showCapabilitiesModal = false;
    }

    public function testClick()
    {
        $this->alert('success', 'Button click works!');
    }

    public function sendMessage()
    {
        // Also dispatch an event for frontend debugging
        $this->dispatch('send-message-clicked');

        // Trim and validate message
        $this->message = trim($this->message);

        if (empty($this->message)) {
            $this->alert('warning', 'Please enter a message');
            return;
        }

        if (!app()->environment('demo')) {
            // Check if API key is configured
            try {
                $globalSetting = \Modules\Aitools\Entities\AiToolsGlobalSetting::first();
                if (empty($globalSetting->openai_api_key)) {
                    $this->alert('error', __('aitools::app.core.apiKeyNotConfiguredError'), [
                        'toast' => true,
                        'position' => 'top-end',
                        'timer' => 10000,
                    ]);
                    return;
                }
            } catch (\Exception $e) {
                $this->alert('error', __('aitools::app.core.apiKeyNotConfiguredError'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'timer' => 10000,
                ]);
                return;
            }
        }
        // Check access again
        $policy = new AiPolicy();
        $restaurant = restaurant();
        $currentUser = user();

        if (!$restaurant || !$currentUser) {
            $this->alert('error', 'Restaurant or user not found');
            return;
        }

        $accessCheck = $policy->canAccess($currentUser, $restaurant);
        if (!$accessCheck['allowed']) {
            $this->alert('error', $accessCheck['reason'] ?? 'Access denied');
            return;
        }

        $this->isLoading = true;
        $this->startTime = now();
        $this->dispatch('message-sent');
        $this->dispatch('scroll-to-bottom');

        try {
            // Initialize orchestrator
            if (!$this->orchestrator) {
                $client = new OpenAIClient();
                $registry = new ToolRegistry();
                $logger = new AiLogger();
                $policy = new AiPolicy();
                $systemPrompt = new SystemPrompt();

                // Register tools
                AiToolServiceProvider::registerTools($registry);

                $this->orchestrator = new AiOrchestrator(
                    $client,
                    $registry,
                    $logger,
                    $policy,
                    $systemPrompt
                );
            }

            $conversation = $this->conversationId
                ? \Modules\Aitools\Entities\AiConversation::find($this->conversationId)
                : null;

            $result = $this->orchestrator->processMessage(
                restaurant(),
                user(),
                $conversation,
                $this->message
            );

            if (isset($result['error'])) {
                $this->alert('error', $result['error']);
                $this->isLoading = false;
                return;
            }

            // Update conversation ID
            if (!$this->conversationId) {
                $this->conversationId = $result['conversation_id'];
            }

            // Clear input immediately for better UX
            $this->message = '';

            // Reload messages from database to ensure we have the latest
            $this->conversationId = $result['conversation_id'];
            $this->loadConversation();

            $this->remainingTokens = $result['remaining_tokens'] ?? 0;
            $this->loadConversations();

            // Dispatch event for auto-scroll
            $this->dispatch('messages-updated');
        } catch (\Exception $e) {
            // Check if error is about API key not being configured
            if ($e->getMessage() === 'API_KEY_NOT_CONFIGURED') {
                $this->alert('error', __('aitools::app.core.apiKeyNotConfiguredError'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'timer' => 10000,
                ]);
            } else {
                $this->alert('error', 'Error: ' . $e->getMessage());
            }

            $this->isLoading = false;
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Get available AI capabilities based on registered tools
     */
    public function getAvailableCapabilities(): array
    {
        try {
            $registry = new ToolRegistry();
            AiToolServiceProvider::registerTools($registry);

            $tools = $registry->getTools();
            $capabilities = [];

            // Map tool names to user-friendly categories and examples
            $toolMapping = [
                'get_sales_by_day' => [
                    'category' => 'Sales & Revenue',
                    'examples' => [
                        'What were the sales this week?',
                        'Show me daily sales for last month',
                        'How much revenue did we make today?'
                    ]
                ],
                'get_orders' => [
                    'category' => 'Orders',
                    'examples' => [
                        'How many orders were paid this week?',
                        'Show me all pending orders',
                        'List orders from yesterday'
                    ]
                ],
                'get_top_items' => [
                    'category' => 'Menu Items',
                    'examples' => [
                        'What are the top selling items?',
                        'Show me best selling items this month',
                        'Which items sold the most?'
                    ]
                ],
                'get_item_report' => [
                    'category' => 'Item Performance',
                    'examples' => [
                        'Show me item sales report',
                        'Which items are performing well?',
                        'Item sales analysis'
                    ]
                ],
                'get_category_report' => [
                    'category' => 'Category Analysis',
                    'examples' => [
                        'Which category has the most sales?',
                        'Show me category performance',
                        'Category sales breakdown'
                    ]
                ],
                'get_tax_report' => [
                    'category' => 'Taxes',
                    'examples' => [
                        'How much tax did we collect?',
                        'Show me tax breakdown',
                        'Tax report for this month'
                    ]
                ],
                'get_cancelled_order_report' => [
                    'category' => 'Cancellations',
                    'examples' => [
                        'How many orders were cancelled?',
                        'Show me cancellation reasons',
                        'Cancelled orders analysis'
                    ]
                ],
                'get_refund_report' => [
                    'category' => 'Refunds',
                    'examples' => [
                        'How much was refunded this month?',
                        'Show me refund details',
                        'Refund report'
                    ]
                ],
                'get_delivery_app_report' => [
                    'category' => 'Delivery Apps',
                    'examples' => [
                        'Which delivery app has most orders?',
                        'Show me delivery app performance',
                        'Delivery commissions breakdown'
                    ]
                ],
                'get_reservations' => [
                    'category' => 'Reservations',
                    'examples' => [
                        'Show me upcoming reservations',
                        'List reservations for today',
                        'Reservation details'
                    ]
                ],
                'get_reservation_stats' => [
                    'category' => 'Reservation Statistics',
                    'examples' => [
                        'How many reservations this week?',
                        'Reservation statistics',
                        'Reservation trends'
                    ]
                ],
                'get_kot_delays' => [
                    'category' => 'Kitchen Performance',
                    'examples' => [
                        'What are the KOT delays?',
                        'Show me kitchen order delays',
                        'KOT performance analysis'
                    ]
                ],
                'get_inventory_usage' => [
                    'category' => 'Inventory',
                    'examples' => [
                        'What inventory was used?',
                        'Show me inventory usage',
                        'Inventory consumption report'
                    ]
                ],
            ];

            foreach ($tools as $tool) {
                $toolName = $tool['function']['name'] ?? '';
                if (isset($toolMapping[$toolName])) {
                    $capabilities[] = [
                        'name' => $toolName,
                        'description' => $tool['function']['description'] ?? '',
                        'category' => $toolMapping[$toolName]['category'],
                        'examples' => $toolMapping[$toolName]['examples'] ?? [],
                    ];
                }
            }

            // Group by category
            $grouped = [];
            foreach ($capabilities as $cap) {
                $category = $cap['category'];
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $cap;
            }

            return $grouped;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function render()
    {
        return view('aitools::livewire.ai.chat', [
            'capabilities' => $this->getAvailableCapabilities(),
        ]);
    }
}

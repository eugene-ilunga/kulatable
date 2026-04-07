<?php

namespace Modules\Aitools\Services\Ai;

use Modules\Aitools\Entities\AiConversation;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AiOrchestrator
{
    private OpenAIClient $client;
    private ToolRegistry $toolRegistry;
    private AiLogger $logger;
    private AiPolicy $policy;
    private SystemPrompt $systemPrompt;

    public function __construct(
        OpenAIClient $client,
        ToolRegistry $toolRegistry,
        AiLogger $logger,
        AiPolicy $policy,
        SystemPrompt $systemPrompt
    ) {
        $this->client = $client;
        $this->toolRegistry = $toolRegistry;
        $this->logger = $logger;
        $this->policy = $policy;
        $this->systemPrompt = $systemPrompt;
    }

    /**
     * Process a user message and return AI response
     */
    public function processMessage(
        Restaurant $restaurant,
        User $user,
        ?AiConversation $conversation,
        string $userMessage
    ): array {
        // Check access
        $accessCheck = $this->policy->canAccess($user, $restaurant);
        if (!$accessCheck['allowed']) {
            return [
                'error' => $accessCheck['reason'],
            ];
        }

        // Create or get conversation
        if (!$conversation) {
            $conversation = AiConversation::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $user->id,
                'title' => $this->generateTitle($userMessage),
            ]);
        }

        // Log user message
        $this->logger->logMessage($conversation, 'user', $userMessage);

        // Build messages array
        $messages = $this->buildMessages($conversation, $userMessage);

        // Get tools
        $tools = $this->toolRegistry->getTools();

        // Call OpenAI
        $maxIterations = 5;
        $iteration = 0;
        $finalResponse = null;

        while ($iteration < $maxIterations) {
            $response = $this->client->chat($messages, $tools);
            $choice = $response['choices'][0];
            $message = $choice['message'];

            // Log assistant message
            $tokensUsed = $this->client->getTokensUsed($response);
            $this->logger->logUsage($restaurant, $tokensUsed);

            // Check if tool calls are needed
            if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
                // Add assistant message with tool calls
                $assistantMsg = [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => $message['tool_calls'],
                ];
                $messages[] = $assistantMsg;

                // Execute tool calls
                $toolResults = [];
                foreach ($message['tool_calls'] as $toolCall) {
                    $toolName = $toolCall['function']['name'];
                    $toolArgs = json_decode($toolCall['function']['arguments'], true);
                    
                    $result = $this->toolRegistry->execute($toolName, $toolArgs);
                    
                    $toolResults[] = [
                        'tool_call_id' => $toolCall['id'],
                        'role' => 'tool',
                        'name' => $toolName,
                        'content' => json_encode($result),
                    ];
                }

                // Add tool results to messages
                $messages = array_merge($messages, $toolResults);
                
                $iteration++;
                continue;
            }

            // Final response
            $finalResponse = $message['content'] ?? '';
            break;
        }

        if (!$finalResponse) {
            return [
                'error' => 'Failed to get response from AI',
            ];
        }

        // Parse response (expecting JSON with answer, widgets, followups)
        $parsedResponse = $this->parseResponse($finalResponse);

        // Log assistant message
        $this->logger->logMessage($conversation, 'assistant', $parsedResponse, $tokensUsed);

        return [
            'conversation_id' => $conversation->id,
            'response' => $parsedResponse,
            'tokens_used' => $tokensUsed,
            'remaining_tokens' => $this->policy->getRemainingTokens($restaurant),
        ];
    }

    /**
     * Build messages array for OpenAI
     */
    private function buildMessages(AiConversation $conversation, string $userMessage): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt->getPrompt(),
            ],
        ];

        // Add conversation history
        $history = $conversation->messages()
            ->where('role', '!=', 'tool')
            ->orderBy('created_at')
            ->get();

        foreach ($history as $msg) {
            $content = is_array($msg->content) ? ($msg->content['text'] ?? json_encode($msg->content)) : $msg->content;
            
            if ($msg->role === 'assistant' && isset($msg->content['answer'])) {
                // Format assistant response
                $content = json_encode($msg->content);
            }

            $messages[] = [
                'role' => $msg->role,
                'content' => $content,
            ];
        }

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $messages;
    }

    /**
     * Parse AI response (expecting JSON)
     */
    private function parseResponse(string $response): array
    {
        // Try to extract JSON from response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
            $parsed = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }
        }

        // Fallback: return as simple text answer
        return [
            'answer' => $response,
            'widgets' => [],
            'followups' => [],
        ];
    }

    /**
     * Generate conversation title from first message
     */
    private function generateTitle(string $message): string
    {
        $title = substr($message, 0, 50);
        if (strlen($message) > 50) {
            $title .= '...';
        }
        return $title;
    }
}


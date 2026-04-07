<?php

namespace Modules\Aitools\Services\Ai;

use Modules\Aitools\Entities\AiConversation;
use Modules\Aitools\Entities\AiMessage;
use Modules\Aitools\Entities\AiUsageDaily;
use App\Models\Restaurant;

class AiLogger
{
    /**
     * Log a message to a conversation
     */
    public function logMessage(AiConversation $conversation, string $role, $content, ?int $tokensUsed = null): AiMessage
    {
        return AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content' => is_array($content) ? $content : ['text' => $content],
            'tokens_used' => $tokensUsed,
        ]);
    }

    /**
     * Log usage statistics
     */
    public function logUsage(Restaurant $restaurant, int $tokensUsed): void
    {
        $usage = AiUsageDaily::firstOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'date' => now()->toDateString(),
            ],
            [
                'requests_count' => 0,
                'tokens_count' => 0,
            ]
        );

        $usage->increment('requests_count');
        $usage->increment('tokens_count', $tokensUsed);

        // Increment monthly token count
        $restaurant->refresh(); // Refresh to get latest data
        $restaurant->increment('ai_monthly_tokens_used', $tokensUsed);
    }
}


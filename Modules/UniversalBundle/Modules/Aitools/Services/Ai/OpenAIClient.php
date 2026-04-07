<?php

namespace Modules\Aitools\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIClient
{
    private string $apiKey;
    private ?string $organizationId;
    private string $model;

    public function __construct()
    {
        // Get API key from AiToolsGlobalSetting only (not from .env/config)
        try {
            $globalSetting = \Modules\Aitools\Entities\AiToolsGlobalSetting::first();

            if (app()->environment('demo')) {
                $this->apiKey = config('services.openai.api_key');
                $this->organizationId = config('services.openai.organization_id');
            } else {
                $this->apiKey = $globalSetting->openai_api_key ?? '';
                $this->organizationId = $globalSetting->openai_organization_id ?? null;
            }
        } catch (\Exception $e) {
            $this->apiKey = '';
            $this->organizationId = null;
        }

        $this->model = config('services.openai.model', 'gpt-4.1-nano');
    }

    /**
     * Get headers for OpenAI API requests including organization if set
     */
    private function getHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if (!empty($this->organizationId)) {
            $headers['OpenAI-Organization'] = $this->organizationId;
        }

        return $headers;
    }

    /**
     * Send a chat completion request to OpenAI
     *
     * @param array $messages Array of message objects with role and content
     * @param array $tools Array of tool definitions
     * @return array
     * @throws \Exception
     */
    public function chat(array $messages, array $tools = []): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('API_KEY_NOT_CONFIGURED');
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(60)->post('https://api.openai.com/v1/chat/completions', $payload);

            if ($response->failed()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['choices'][0])) {
                throw new \Exception('Invalid response from OpenAI API');
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract tokens used from response
     */
    public function getTokensUsed(array $response): int
    {
        return $response['usage']['total_tokens'] ?? 0;
    }
}

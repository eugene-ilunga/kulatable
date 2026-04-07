<?php

namespace Modules\Aitools\Services\Ai;

class ToolRegistry
{
    private array $tools = [];
    private array $handlers = [];

    public function __construct()
    {
        $this->registerDefaultTools();
    }

    /**
     * Register a tool with its handler
     */
    public function register(string $name, array $definition, callable $handler): void
    {
        $this->tools[] = [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $definition['description'] ?? '',
                'parameters' => [
                    'type' => 'object',
                    'properties' => $definition['parameters'] ?? [],
                    'required' => $definition['required'] ?? [],
                ],
            ],
        ];

        $this->handlers[$name] = $handler;
    }

    /**
     * Get all registered tools for OpenAI
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * Execute a tool by name
     */
    public function execute(string $name, array $arguments): array
    {
        if (!isset($this->handlers[$name])) {
            return [
                'error' => "Tool '{$name}' not found",
            ];
        }

        try {
            return call_user_func($this->handlers[$name], $arguments);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Register default tools
     */
    private function registerDefaultTools(): void
    {
        // Tools will be registered by AiOrchestrator after instantiation
    }

    /**
     * Set tool handlers (called by AiOrchestrator)
     */
    public function setHandlers(array $handlers): void
    {
        $this->handlers = array_merge($this->handlers, $handlers);
    }
}







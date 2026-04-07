<?php

namespace Modules\Webhooks\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookEventOccurred implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $eventName;
    public ?int $restaurantId;
    public ?int $branchId;
    public array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $eventName,
        ?int $restaurantId,
        ?int $branchId,
        array $data
    ) {
        $this->eventName = $eventName;
        $this->restaurantId = $restaurantId;
        $this->branchId = $branchId;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to restaurant channel
        if ($this->restaurantId) {
            $channels[] = new Channel("restaurant.{$this->restaurantId}");
        }

        // Broadcast to branch-specific channel if branch is specified
        if ($this->branchId) {
            $channels[] = new Channel("branch.{$this->branchId}");
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'event' => $this->eventName,
            'restaurant_id' => $this->restaurantId,
            'branch_id' => $this->branchId,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

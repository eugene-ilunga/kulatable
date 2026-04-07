<?php

namespace Modules\Webhooks\Livewire\SuperAdmin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Modules\Webhooks\Entities\SystemWebhook;
use Modules\Webhooks\Entities\SystemWebhookDelivery;
use Modules\Webhooks\Jobs\DispatchSystemWebhook;

class SystemWebhooks extends Component
{
    use WithPagination;

    // Form fields
    public $name = '';
    public $description = '';
    public $target_url = '';
    public $secret = '';
    public $is_active = true;
    public $max_attempts = 3;
    public $backoff_seconds = 60;
    public $subscribed_events = [];

    // Modal states
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $editingWebhookId = null;
    public $deletingWebhookId = null;

    // Available events
    public array $availableEvents = [
        'order.created',
        'order.updated',
        'order.cancelled',
        'order.paid',
        'reservation.received',
        'reservation.confirmed',
        'kot.updated',
        'payment.success',
        'payment.failed',
        'restaurant.created',
    ];

    public function mount(): void
    {
        // Super admin only
        abort_if(!is_null(user()->restaurant_id), 403);
    }

    public function render()
    {
        $webhooks = SystemWebhook::latest()->paginate(10);
        $recentDeliveries = SystemWebhookDelivery::latest()->limit(10)->get();

        return view('webhooks::livewire.super-admin.system-webhooks', [
            'webhooks' => $webhooks,
            'recentDeliveries' => $recentDeliveries,
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function create(): void
    {
        $this->validate($this->rules());

        SystemWebhook::create([
            'name' => $this->name,
            'description' => $this->description,
            'target_url' => $this->target_url,
            'secret' => $this->secret ?: Str::random(40),
            'is_active' => $this->is_active,
            'max_attempts' => $this->max_attempts,
            'backoff_seconds' => $this->backoff_seconds,
            'subscribed_events' => empty($this->subscribed_events) ? null : $this->subscribed_events,
        ]);

        $this->closeCreateModal();
        $this->dispatch('notify', type: 'success', message: __('webhooks::webhooks.webhook_created'));
    }

    public function openEditModal(int $webhookId): void
    {
        $webhook = SystemWebhook::findOrFail($webhookId);
        $this->editingWebhookId = $webhookId;
        $this->name = $webhook->name;
        $this->description = $webhook->description ?? '';
        $this->target_url = $webhook->target_url;
        $this->secret = '';
        $this->is_active = $webhook->is_active;
        $this->max_attempts = $webhook->max_attempts;
        $this->backoff_seconds = $webhook->backoff_seconds;
        $this->subscribed_events = $webhook->subscribed_events ?? [];
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingWebhookId = null;
        $this->resetForm();
    }

    public function update(): void
    {
        $this->validate($this->rules());

        $webhook = SystemWebhook::findOrFail($this->editingWebhookId);
        
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'target_url' => $this->target_url,
            'is_active' => $this->is_active,
            'max_attempts' => $this->max_attempts,
            'backoff_seconds' => $this->backoff_seconds,
            'subscribed_events' => empty($this->subscribed_events) ? null : $this->subscribed_events,
        ];

        if ($this->secret) {
            $data['secret'] = $this->secret;
        }

        $webhook->update($data);

        $this->closeEditModal();
        $this->dispatch('notify', type: 'success', message: __('webhooks::webhooks.webhook_updated'));
    }

    public function confirmDelete(int $webhookId): void
    {
        $this->deletingWebhookId = $webhookId;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingWebhookId = null;
    }

    public function delete(): void
    {
        $webhook = SystemWebhook::findOrFail($this->deletingWebhookId);
        $webhook->delete();

        $this->cancelDelete();
        $this->dispatch('notify', type: 'success', message: __('webhooks::webhooks.webhook_deleted'));
    }

    public function toggleActive(int $webhookId): void
    {
        $webhook = SystemWebhook::findOrFail($webhookId);
        $webhook->update(['is_active' => !$webhook->is_active]);
        $this->dispatch('notify', type: 'success', message: __('webhooks::webhooks.webhook_updated'));
    }

    public function sendTest(int $webhookId): void
    {
        $webhook = SystemWebhook::findOrFail($webhookId);

        $payload = [
            'id' => (string) Str::uuid(),
            'event' => 'webhooks.test',
            'created_at' => now()->toIso8601String(),
            'restaurant_id' => null,
            'branch_id' => null,
            'source_module' => 'Webhooks',
            'webhook_type' => 'system',
            'data' => [
                'message' => 'Test webhook from system',
                'webhook_id' => $webhook->id,
            ],
        ];

        $delivery = SystemWebhookDelivery::create([
            'system_webhook_id' => $webhook->id,
            'restaurant_id' => null,
            'event' => $payload['event'],
            'status' => 'pending',
            'attempts' => 0,
            'payload' => $payload,
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        DispatchSystemWebhook::dispatch($delivery);

        $this->dispatch('notify', type: 'success', message: __('webhooks::webhooks.test_queued'));
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->target_url = '';
        $this->secret = '';
        $this->is_active = true;
        $this->max_attempts = 3;
        $this->backoff_seconds = 60;
        $this->subscribed_events = [];
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'target_url' => 'required|url|max:2000',
            'secret' => 'nullable|string|min:16|max:255',
            'is_active' => 'boolean',
            'max_attempts' => 'integer|min:1|max:10',
            'backoff_seconds' => 'integer|min:5|max:3600',
            'subscribed_events' => 'nullable|array',
            'subscribed_events.*' => 'string|max:191',
        ];
    }
}

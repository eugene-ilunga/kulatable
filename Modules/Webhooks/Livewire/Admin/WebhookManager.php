<?php

namespace Modules\Webhooks\Livewire\Admin;
use Livewire\Component;
use Modules\Webhooks\Entities\Webhook;
use Modules\Webhooks\Entities\WebhookDelivery;
use Modules\Webhooks\Jobs\DispatchWebhook;
use Illuminate\Support\Str;

class WebhookManager extends Component
{
    public ?string $accessError = null;
    public $webhooks = [];
    public $recentDeliveries = [];

    public $editingId = null;

    public $name = '';
    public $target_url = '';
    public $secret = '';
    public $is_active = true;
    public $max_attempts = 3;
    public $backoff_seconds = 60;
    public $branch_id = null;
    public $subscribed_events = [];
    public $source_modules = [];
    public $redact_payload = false;

    public function mount(): void
    {
        if (! $this->moduleAllowedForRestaurant()) {
            $this->accessError = 'Webhooks are not enabled for this restaurant.';
            return;
        }
        if (! $this->userAllowed()) {
            $this->accessError = 'You do not have permission to manage webhooks.';
            return;
        }

        $this->refreshData();
    }

    public function render()
    {
        return view('webhooks::livewire.admin.webhook-manager');
    }

    public function save(): void
    {
        if ($this->accessError) {
            return;
        }
        $data = $this->validate($this->rules());

        $restaurantId = restaurant()?->id;
        $branchId = $this->currentBranchId();

        $data['restaurant_id'] = $restaurantId;
        $data['branch_id'] = $branchId;
        $data['subscribed_events'] = empty($data['subscribed_events'] ?? []) ? null : $data['subscribed_events'];
        $data['source_modules'] = empty($data['source_modules'] ?? []) ? null : $data['source_modules'];
        $data['redact_payload'] = $this->redact_payload;

        if ($this->editingId) {
            // Update existing webhook
            $webhook = Webhook::where('restaurant_id', $restaurantId)->findOrFail($this->editingId);
            
            // Only update secret if a new one is provided
            if (!empty($data['secret'])) {
                $webhook->secret = $data['secret'];
            }
            unset($data['secret']);
            
            $webhook->update($data);
            $message = __('Webhook updated');
        } else {
            // Create new webhook
            $data['secret'] = $data['secret'] ?: Str::random(40);
            Webhook::create($data);
            $message = __('Webhook created');
        }

        $this->resetForm();
        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function edit(int $webhookId): void
    {
        if ($this->accessError) {
            return;
        }
        $webhook = $this->findWebhookForBranch($webhookId);

        $this->editingId = $webhook->id;
        $this->name = $webhook->name;
        $this->target_url = $webhook->target_url;
        $this->secret = ''; // Don't populate secret for security
        $this->is_active = $webhook->is_active;
        $this->max_attempts = $webhook->max_attempts;
        $this->backoff_seconds = $webhook->backoff_seconds;
        $this->branch_id = $this->currentBranchId();
        $this->subscribed_events = $webhook->subscribed_events ?? [];
        $this->source_modules = $webhook->source_modules ?? [];
        $this->redact_payload = $webhook->redact_payload;

        // Scroll to form
        $this->dispatch('scroll-to-form');
    }

    public function cancelEdit(): void
    {
        if ($this->accessError) {
            return;
        }
        $this->resetForm();
    }

    public function viewDetails(int $webhookId): void
    {
        if ($this->accessError) {
            return;
        }
        $webhook = $this->findWebhookForBranch($webhookId);
        
        $this->dispatch('show-webhook-details', $webhook->toArray());
    }

    public function delete(int $webhookId): void
    {
        if ($this->accessError) {
            return;
        }
        $webhook = $this->findWebhookForBranch($webhookId);
        $webhook->delete();

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: __('Webhook deleted'));
    }

    public function sendTest(int $webhookId): void
    {
        if ($this->accessError) {
            return;
        }
        $restaurantId = restaurant()?->id;
        $branchId = $this->currentBranchId();

        $webhook = $this->findWebhookForBranch($webhookId);

        $payload = [
            'id' => (string) Str::uuid(),
            'event' => 'webhooks.test',
            'created_at' => now()->toIso8601String(),
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'source_module' => 'Webhooks',
            'data' => [
                'message' => 'Test webhook from TableTrack',
                'webhook_id' => $webhook->id,
            ],
        ];

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
            'event' => $payload['event'],
            'status' => 'pending',
            'attempts' => 0,
            'payload' => $payload,
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        DispatchWebhook::dispatch($delivery);

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: __('Test queued'));
    }

    public function replay(int $deliveryId): void
    {
        if ($this->accessError) {
            return;
        }
        $restaurantId = restaurant()?->id;
        $branchId = $this->currentBranchId();
        $delivery = WebhookDelivery::where('restaurant_id', $restaurantId)
            ->where('branch_id', $branchId)
            ->findOrFail($deliveryId);

        $webhook = $this->findWebhookForBranch($delivery->webhook_id);

        $payload = $delivery->payload ?? [];
        $payload['id'] = (string) Str::uuid();
        $payload['created_at'] = now()->toIso8601String();

        $newDelivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'restaurant_id' => $delivery->restaurant_id,
            'branch_id' => $delivery->branch_id,
            'event' => $delivery->event,
            'status' => 'pending',
            'attempts' => 0,
            'payload' => $payload,
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        DispatchWebhook::dispatch($newDelivery);

        $this->refreshData();
        $this->dispatch('notify', type: 'success', message: __('Replay queued'));
    }

    private function refreshData(): void
    {
        $restaurantId = restaurant()?->id;
        $branchId = $this->currentBranchId();

        $this->webhooks = Webhook::query()
            ->where('restaurant_id', $restaurantId)
            ->where('branch_id', $branchId)
            ->latest()
            ->get()
            ->toArray();

        $this->recentDeliveries = WebhookDelivery::query()
            ->where('restaurant_id', $restaurantId)
            ->where('branch_id', $branchId)
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->target_url = '';
        $this->secret = '';
        $this->is_active = true;
        $this->max_attempts = 3;
        $this->backoff_seconds = 60;
        $this->branch_id = $this->currentBranchId();
        $this->subscribed_events = [];
        $this->source_modules = [];
        $this->redact_payload = false;
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'target_url' => 'required|url|max:2000',
            'secret' => 'nullable|string|min:16|max:255',
            'is_active' => 'boolean',
            'max_attempts' => 'integer|min:1|max:10',
            'backoff_seconds' => 'integer|min:5|max:3600',
            'subscribed_events' => 'nullable|array',
            'subscribed_events.*' => 'string|max:191',
            'source_modules' => 'nullable|array',
            'source_modules.*' => 'string|max:191',
            'redact_payload' => 'boolean',
        ];
    }

    private function currentBranchId(): ?int
    {
        return branch()?->id;
    }

    private function findWebhookForBranch(int $webhookId): Webhook
    {
        $restaurantId = restaurant()?->id;
        $branchId = $this->currentBranchId();

        return Webhook::where('restaurant_id', $restaurantId)
            ->where('branch_id', $branchId)
            ->findOrFail($webhookId);
    }

    private function moduleAllowedForRestaurant(): bool
    {
        $restaurant = restaurant();
        if (! $restaurant) {
            return false;
        }

        if (function_exists('restaurant_modules') && in_array('Webhooks', restaurant_modules(), true)) {
            return true;
        }

        $packageId = $restaurant->package_id;
        if (! $packageId) {
            return false;
        }

        return \App\Models\Module::where('name', 'Webhooks')
            ->whereHas('packages', fn($q) => $q->where('packages.id', $packageId))
            ->exists();
    }

    private function userAllowed(): bool
    {
        if (user_can('Manage Webhooks') || user_can('View Webhook Logs')) {
            return true;
        }

        $role = user()->roles->first();
        return $role && str_starts_with($role->name, 'Admin_');
    }
}

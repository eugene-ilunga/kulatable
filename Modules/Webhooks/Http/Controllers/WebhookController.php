<?php

namespace Modules\Webhooks\Http\Controllers;

use App\Helper\Reply;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Webhooks\Entities\Webhook;
use Modules\Webhooks\Entities\WebhookDelivery;
use Modules\Webhooks\Jobs\DispatchWebhook;

class WebhookController extends Controller
{
    public function index()
    {
        $this->authorizeAccess();

        $restaurantId = restaurant()?->id;
        $branchId = branch()?->id;

        $webhooks = Webhook::query()
            ->withCount('deliveries')
            ->where('restaurant_id', $restaurantId)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id');
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->latest()
            ->get();

        if (request()->wantsJson()) {
            return response()->json($webhooks);
        }

        return view('webhooks::index', compact('webhooks'));
    }

    public function showDelivery(WebhookDelivery $delivery)
    {
        $this->authorizeAccess();
        $this->authorizeDeliveryTenant($delivery);

        return response()->json([
            'id' => $delivery->id,
            'event' => $delivery->event,
            'status' => $delivery->status,
            'attempts' => $delivery->attempts,
            'response_code' => $delivery->response_code,
            'response_body' => $delivery->response_body,
            'error_message' => $delivery->error_message,
            'payload' => $delivery->payload,
            'created_at' => $delivery->created_at,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->validatedData($request);
        $data['restaurant_id'] = restaurant()?->id;
        $data['branch_id'] = $request->input('branch_id', branch()?->id);
        $data['secret'] = $data['secret'] ?? Str::random(40);

        $webhook = Webhook::create($data);

        return Reply::successWithData(__('Webhook created'), ['webhook' => $webhook]);
    }

    public function update(Request $request, Webhook $webhook)
    {
        $this->authorizeAccess();
        $this->authorizeTenant($webhook);

        $data = $this->validatedData($request, $webhook->id);
        $webhook->update($data);

        return Reply::successWithData(__('Webhook updated'), ['webhook' => $webhook]);
    }

    public function destroy(Webhook $webhook)
    {
        $this->authorizeAccess();
        $this->authorizeTenant($webhook);

        $webhook->delete();

        return Reply::success(__('Webhook deleted'));
    }

    public function replay(WebhookDelivery $delivery)
    {
        $this->authorizeAccess();
        $this->authorizeDeliveryTenant($delivery);

        $webhook = Webhook::findOrFail($delivery->webhook_id);
        $this->authorizeTenant($webhook);

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

        return Reply::successWithData(__('Replay queued'), ['delivery_id' => $newDelivery->id]);
    }

    public function sendTest(Request $request, Webhook $webhook)
    {
        $this->authorizeAccess();
        $this->authorizeTenant($webhook);

        $payload = [
            'id' => (string) Str::uuid(),
            'event' => 'webhooks.test',
            'created_at' => now()->toIso8601String(),
            'restaurant_id' => restaurant()?->id,
            'branch_id' => branch()?->id,
            'data' => [
                'message' => 'Test webhook from TableTrack',
                'url' => $webhook->target_url,
            ],
        ];

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'restaurant_id' => $webhook->restaurant_id,
            'branch_id' => $webhook->branch_id,
            'event' => $payload['event'],
            'status' => 'pending',
            'attempts' => 0,
            'payload' => $webhook->redact_payload ? \Modules\Webhooks\Support\Redactor::apply($payload) : $payload,
            'idempotency_key' => Str::uuid()->toString(),
        ]);

        DispatchWebhook::dispatch($delivery);

        return Reply::successWithData(__('Test queued'), ['delivery_id' => $delivery->id]);
    }

    private function authorizeAccess(): void
    {
        abort_if(! $this->moduleAllowedForRestaurant(), 403);
        abort_if(! $this->userAllowed(), 403);
    }

    private function authorizeTenant(Webhook $webhook): void
    {
        $restaurantId = restaurant()?->id;
        abort_if($restaurantId && $webhook->restaurant_id !== $restaurantId, 403);
    }

    private function authorizeDeliveryTenant(WebhookDelivery $delivery): void
    {
        $restaurantId = restaurant()?->id;
        abort_if($restaurantId && $delivery->restaurant_id !== $restaurantId, 403);
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'target_url' => 'required|url|max:2000',
            'secret' => 'nullable|string|min:16|max:255',
            'is_active' => 'boolean',
            'max_attempts' => 'integer|min:1|max:10',
            'backoff_seconds' => 'integer|min:5|max:3600',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'subscribed_events' => 'nullable|array',
            'subscribed_events.*' => 'string|max:191',
            'source_modules' => 'nullable|array',
            'source_modules.*' => 'string|max:191',
            'custom_headers' => 'nullable|array',
            'redact_payload' => 'boolean',
        ];

        $data = $request->validate($rules);

        if (isset($data['branch_id']) && $data['branch_id']) {
            $validBranch = branch()?->id === $data['branch_id'] || (restaurant()?->branches()->where('id', $data['branch_id'])->exists());
            abort_if(! $validBranch, 403);
        }

        // Enforce https unless explicitly allowed via config
        if (isset($data['target_url']) && ! $this->httpsAllowed($data['target_url'])) {
            abort(422, __('Webhook URL must be HTTPS'));
        }

        return $data;
    }

    private function httpsAllowed(string $url): bool
    {
        if (config('app.env') === 'local') {
            return true;
        }

        return str_starts_with(strtolower($url), 'https://');
    }

    private function moduleAllowedForRestaurant(): bool
    {
        if (is_null(user()?->restaurant_id) && is_null(user()?->branch_id)) {
            return true;
        }

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

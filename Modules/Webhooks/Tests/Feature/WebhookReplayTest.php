<?php

namespace Modules\Webhooks\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Webhooks\Entities\Webhook;
use Modules\Webhooks\Entities\WebhookDelivery;
use Tests\TestCase;

class WebhookReplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'Modules/Webhooks/Database/Migrations', '--realpath' => true]);
    }

    public function test_tenant_can_replay_delivery()
    {
        $user = User::factory()->create(['restaurant_id' => 1]);
        $this->actingAs($user);

        $webhook = Webhook::create([
            'name' => 'Test',
            'target_url' => 'https://example.com',
            'secret' => 'secret',
            'restaurant_id' => 1,
            'is_active' => true,
        ]);

        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'restaurant_id' => 1,
            'event' => 'order.created',
            'status' => 'failed',
            'attempts' => 1,
            'payload' => ['id' => 'abc', 'event' => 'order.created'],
            'idempotency_key' => 'key',
        ]);

        $response = $this->post(route('api.webhooks.deliveries.replay', ['delivery' => $delivery->id]));
        $response->assertStatus(200);
    }
}

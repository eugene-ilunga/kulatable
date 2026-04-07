<?php

namespace Modules\Webhooks\Livewire\SuperAdmin;

use Livewire\Component;
use Modules\Webhooks\Entities\WebhookDelivery;

class DeliveryDetail extends Component
{
    public $deliveryId;
    public $delivery;

    protected $listeners = ['showDelivery' => 'loadDelivery'];

    public function loadDelivery(int $deliveryId): void
    {
        $this->deliveryId = $deliveryId;
        $this->delivery = WebhookDelivery::find($deliveryId);
    }

    public function render()
    {
        return view('webhooks::livewire.super-admin.delivery-detail');
    }
}

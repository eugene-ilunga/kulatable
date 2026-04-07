<?php

namespace Modules\Webhooks\Livewire\SuperAdmin;

use App\Models\Restaurant;
use Livewire\Component;
use Modules\Webhooks\Entities\Webhook;
use Modules\Webhooks\Entities\WebhookDelivery;

class Dashboard extends Component
{
    public $restaurants = [];
    public $selectedRestaurant = null;
    public $summary = [];
    public $deliveries = [];

    public function mount(): void
    {
        abort_if(!is_null(user()->restaurant_id), 403);

        $this->restaurants = Restaurant::select('id', 'name')->orderBy('name')->get()->toArray();
        $this->selectedRestaurant = $this->restaurants[0]['id'] ?? null;
        $this->loadData();
    }

    public function updatedSelectedRestaurant(): void
    {
        $this->loadData();
    }

    public function render()
    {
        return view('webhooks::livewire.super-admin.dashboard')->layout('layouts.app');
    }

    private function loadData(): void
    {
        if (! $this->selectedRestaurant) {
            $this->summary = [];
            $this->deliveries = [];
            return;
        }

        $this->summary = [
            'webhooks' => Webhook::where('restaurant_id', $this->selectedRestaurant)->count(),
            'deliveries' => WebhookDelivery::where('restaurant_id', $this->selectedRestaurant)->count(),
            'failed' => WebhookDelivery::where('restaurant_id', $this->selectedRestaurant)->where('status', 'failed')->count(),
            'pending' => WebhookDelivery::where('restaurant_id', $this->selectedRestaurant)->where('status', 'pending')->count(),
        ];

        $this->deliveries = WebhookDelivery::where('restaurant_id', $this->selectedRestaurant)
            ->latest()
            ->limit(15)
            ->get()
            ->toArray();
    }
}

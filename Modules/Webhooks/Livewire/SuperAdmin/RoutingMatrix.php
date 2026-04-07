<?php

namespace Modules\Webhooks\Livewire\SuperAdmin;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RoutingMatrix extends Component
{
    public array $matrix = [];
    public bool $saving = false;

    public function mount(): void
    {
        $this->matrix = $this->loadMatrix();
    }

    public function render()
    {
        return view('webhooks::livewire.super-admin.routing-matrix')
            ->layout('layouts.app');
    }

    public function toggle(string $module, string $eventKey): void
    {
        $current = $this->matrix[$module]['events'][$eventKey]['allowed'] ?? true;
        $this->matrix[$module]['events'][$eventKey]['allowed'] = ! $current;
    }

    public function save(): void
    {
        $this->saving = true;

        foreach ($this->matrix as $module => $entry) {
            foreach ($entry['events'] as $eventKey => $event) {
                DB::table('webhook_global_policies')->updateOrInsert(
                    [
                        'event_key' => $eventKey,
                        'module' => $module,
                    ],
                    [
                        'allowed' => $event['allowed'],
                        'allowed_packages' => $event['allowed_packages'] ? json_encode($event['allowed_packages']) : null,
                        'updated_at' => now(),
                        'created_at' => $event['created_at'] ?? now(),
                    ]
                );
            }
        }

        $this->saving = false;
    }

    private function loadMatrix(): array
    {
        $matrix = [];

        $catalog = DB::table('webhook_event_catalog')
            ->orderBy('module')
            ->orderBy('event_key')
            ->get()
            ->groupBy('module');

        $policies = DB::table('webhook_global_policies')
            ->get()
            ->keyBy(fn ($row) => $row->module.'|'.$row->event_key);

        foreach ($catalog as $module => $events) {
            $matrix[$module] = [
                'module' => $module,
                'events' => [],
            ];

            foreach ($events as $evt) {
                $key = $module.'|'.$evt->event_key;
                $policy = $policies->get($key);
                $matrix[$module]['events'][$evt->event_key] = [
                    'event_key' => $evt->event_key,
                    'description' => $evt->description,
                    'schema_version' => $evt->schema_version,
                    'allowed' => $policy ? (bool) $policy->allowed : true,
                    'allowed_packages' => $policy && $policy->allowed_packages ? json_decode($policy->allowed_packages, true) : null,
                ];
            }
        }

        return $matrix;
    }
}

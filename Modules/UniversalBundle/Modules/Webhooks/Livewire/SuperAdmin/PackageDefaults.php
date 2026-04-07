<?php

namespace Modules\Webhooks\Livewire\SuperAdmin;

use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PackageDefaults extends Component
{
    public array $packages = [];
    public array $catalog = [];
    public ?int $selectedPackage = null;
    public array $form = [
        'allowed_events' => [],
        'auto_provision' => true,
        'default_target_url' => null,
        'default_secret' => null,
        'rotate_interval_days' => null,
    ];
    public bool $saving = false;

    public function mount(): void
    {
        $this->packages = Package::select('id', 'name')->orderBy('name')->get()->toArray();
        $this->catalog = DB::table('webhook_event_catalog')->orderBy('module')->orderBy('event_key')->get()->toArray();
        $this->selectedPackage = $this->packages[0]['id'] ?? null;
        if ($this->selectedPackage) {
            $this->loadDefaults($this->selectedPackage);
        }
    }

    public function render()
    {
        return view('webhooks::livewire.super-admin.package-defaults')
            ->layout('layouts.app');
    }

    public function updatedSelectedPackage($value): void
    {
        $this->loadDefaults((int) $value);
    }

    public function toggleEvent(string $eventKey): void
    {
        if (in_array($eventKey, $this->form['allowed_events'], true)) {
            $this->form['allowed_events'] = array_values(array_diff($this->form['allowed_events'], [$eventKey]));
        } else {
            $this->form['allowed_events'][] = $eventKey;
        }
    }

    public function save(): void
    {
        if (! $this->selectedPackage) {
            return;
        }

        $this->saving = true;

        DB::table('webhook_package_defaults')->updateOrInsert(
            ['package_id' => $this->selectedPackage],
            [
                'allowed_events' => json_encode(array_values($this->form['allowed_events'])),
                'auto_provision' => (bool) $this->form['auto_provision'],
                'default_target_url' => $this->form['default_target_url'],
                'default_secret' => $this->form['default_secret'],
                'rotate_interval_days' => $this->form['rotate_interval_days'] ?: null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->saving = false;
    }

    private function loadDefaults(int $packageId): void
    {
        $row = DB::table('webhook_package_defaults')->where('package_id', $packageId)->first();

        $this->form = [
            'allowed_events' => $row && $row->allowed_events ? json_decode($row->allowed_events, true) : array_map(fn ($c) => $c->event_key, $this->catalog),
            'auto_provision' => $row ? (bool) $row->auto_provision : true,
            'default_target_url' => $row->default_target_url ?? null,
            'default_secret' => $row->default_secret ?? null,
            'rotate_interval_days' => $row->rotate_interval_days ?? null,
        ];
    }
}

<?php

namespace Modules\Inventory\Livewire\Setting;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\InventorySetting;

class PurchaseOrderSetting extends Component
{
    use LivewireAlert;

    public $settings;
    public bool $allowPurchaseOrder;
    public bool $sendStockSummaryEmail;

    public function mount()
    {
        $this->settings = InventorySetting::first();

        $this->allowPurchaseOrder = (bool) $this->settings->allow_auto_purchase;
        $this->sendStockSummaryEmail = (bool) $this->settings->send_stock_summary_email;
    }

    public function submitForm()
    {
        $this->settings->allow_auto_purchase = $this->allowPurchaseOrder;
        $this->settings->send_stock_summary_email = $this->sendStockSummaryEmail;
        $this->settings->save();

        $this->alert('success', __('messages.settingsUpdated'));
    }

    public function render()
    {
        return view('inventory::livewire.setting.purchase-order-setting');
    }
}

<?php

namespace App\Livewire\Shop;

use App\Models\Branch;
use App\Models\FreshpayPayment;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Component;

class OrderSuccess extends Component
{

    public $id;
    public $order;
    public $restaurant;
    public $shopBranch;
    public $dateFormat;
    public $timeFormat;
    public $freshpayConfirmed = false;

    public function mount()
    {
        $this->order = Order::with('taxes.tax', 'items.menuItem')->where('id', $this->id)->firstOrFail();

        if (is_null(customer()) && $this->restaurant->customer_login_required) {
            return $this->redirect(route('home'));
        }

        if (request()->branch && request()->branch != '') {
            $this->shopBranch = Branch::find(request()->branch);
        } else {
            $this->shopBranch = $this->restaurant->branches->first();
        }

        // Set date and time formats
        $this->dateFormat = $this->restaurant->date_format ?? dateFormat();
        $this->timeFormat = $this->restaurant->time_format ?? timeFormat();
    }

    public function render()
    {
        return view('livewire.shop.order-success');
    }

    public function refreshOrderSuccess()
    {
        $this->dispatch('$refresh');
    }

    public function pollOrderStatus()
    {
        $previousStatus = $this->order->status;
        $this->order = Order::with('taxes.tax', 'items.menuItem')->where('id', $this->id)->firstOrFail();

        if ($previousStatus === 'pending_verification' && $this->order->status === 'paid') {
            $this->freshpayConfirmed = true;
        }
    }

    public function cancelPendingFreshpay()
    {
        if ($this->order->status !== 'pending_verification') {
            return;
        }

        $pendingFreshpay = FreshpayPayment::where('order_id', $this->order->id)
            ->where('payment_status', 'pending')
            ->latest()
            ->first();

        if ($pendingFreshpay) {
            $pendingFreshpay->payment_status = 'failed';
            $pendingFreshpay->trans_status = 'cancelled';
            $pendingFreshpay->trans_status_description = 'Cancelled by customer before confirmation.';
            $pendingFreshpay->save();
        }

        Payment::where('order_id', $this->order->id)
            ->where('payment_method', 'freshpay')
            ->delete();

        $this->order->update([
            'status' => 'payment_due',
            'amount_paid' => 0,
        ]);

        $this->order = Order::with('taxes.tax', 'items.menuItem')->where('id', $this->id)->firstOrFail();
        $this->freshpayConfirmed = false;
    }
}

<?php

namespace App\Livewire\Dashboard;

use App\Events\TodayOrdersUpdated;
use App\Models\Kot;
use App\Models\Order;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TodayOrders extends Component
{

    use LivewireAlert;

    public function render()
    {
        // Get business day boundaries for today
        $boundaries = getBusinessDayBoundaries(branch(), now());
        $startUTC = $boundaries['start']->setTimezone('UTC')->toDateTimeString();
        $endUTC = $boundaries['end']->setTimezone('UTC')->toDateTimeString();

        $orderQuery = Order::where('orders.date_time', '>=', $startUTC)
            ->where('orders.date_time', '<=', $endUTC)
            ->where('status', '<>', 'canceled')
            ->where('status', '<>', 'draft');

        // Filter by waiter if user is a waiter
        if (user()->hasRole('Waiter_' . user()->restaurant_id)) {
            $orderQuery->where('waiter_id', user()->id);
        }

        $count = $orderQuery->count();

        $kotQuery = Kot::join('orders', 'kots.order_id', '=', 'orders.id')
            ->where('kots.created_at', '>=', $startUTC)
            ->where('kots.created_at', '<=', $endUTC)
            ->where('orders.status', '<>', 'canceled')
            ->where('orders.status', '<>', 'draft');

        // Filter by waiter if user is a waiter
        if (user()->hasRole('Waiter_' . user()->restaurant_id)) {
            $kotQuery->where('orders.waiter_id', user()->id);
        }

        $todayKotCount = $kotQuery->count();

        $playSound = false;

        if (session()->has('today_order_count') && session('today_order_count') < $todayKotCount) {
            $playSound = true;

            $this->alert('success', __('messages.newOrderReceived'), [
                'toast' => true,
                'position' => 'top-end'
            ]);

            // uncomment to show new order notification in orders list if needed
            // session(['new_order_notification_pending' => true]);
            $this->dispatch('refreshOrders');
        } elseif ($count > session('last_order_count', 0) && !session('new_order_notification_pending')) {
            session(['new_order_notification_pending' => true]);
        }

        session([
            'last_order_count' => $count,
            'today_order_count' => $todayKotCount
        ]);

        return view('livewire.dashboard.today-orders', [
            'count' => $count,
            'playSound' => $playSound,
        ]);
    }

    /**
     * Handle refresh from Pusher event
     */
    public function refreshOrders()
    {
        // This method will be called when Pusher sends data
        // The component will automatically re-render with fresh data
        $this->dispatch('$refresh');
    }
}

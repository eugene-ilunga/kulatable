<?php

namespace Modules\MultiPOS\Livewire\Restaurant;

use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;
use Modules\MultiPOS\Entities\PosMachine;
use Carbon\Carbon;

class MultiPOSUsageTable extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $restaurantId;
    public $selectedYear;
    public $selectedMonth;

    public function mount($restaurantId)
    {
        $this->restaurantId = $restaurantId;
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
    }

    public function render()
    {
        // Get all branches for this restaurant
        $branches = \App\Models\Branch::where('restaurant_id', $this->restaurantId)
            ->pluck('id')
            ->toArray();

        // Get restaurant with currency
        $restaurant = \App\Models\Restaurant::find($this->restaurantId);

        // Calculate start and end of selected month/year
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfDay();
        $endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->endOfDay();

        // Query all POS machines for the selected restaurant (not filtered by creation date)
        $machines = PosMachine::with(['branch'])
            ->whereIn('branch_id', $branches)
            ->get()
            ->map(function($machine) use ($startDate, $endDate) {
                // Get orders for this machine in the selected month/year
                // Use date_time field and filter by paid/payment_due status
                $orders = \App\Models\Order::where('pos_machine_id', $machine->id)
                    ->whereBetween('date_time', [$startDate, $endDate])
                    ->whereIn('status', ['paid', 'payment_due'])
                    ->get();
                
                // Count orders
                $machine->orders_count = $orders->count();
                
                // Calculate revenue from payments (same as sales report)
                $machine->total_revenue = \App\Models\Payment::whereIn('order_id', $orders->pluck('id'))
                    ->sum('amount');
                
                return $machine;
            });

        return view('multipos::livewire.restaurant.multipos-usage-table', [
            'machines' => $machines,
            'restaurant' => $restaurant
        ]);
    }
}


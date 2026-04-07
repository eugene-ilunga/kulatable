<?php

namespace Modules\Loyalty\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\Customer;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Entities\LoyaltyAccount;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Loyalty\Exports\LoyaltyLiabilityExport;

class LoyaltyLiabilityReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'custom';
    public $asOfDate;
    public $branchId = 'all';
    public $customerId = 'all';
    public $branches = [];
    public $customers = [];
    public $valuePerPoint = 1;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        abort_if(!function_exists('restaurant_modules') || !in_array('Loyalty', restaurant_modules()), 403);
        abort_if((!user_can('Show Reports')), 403);

        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $this->dateRangeType = 'custom';
        $this->asOfDate = Carbon::now($tz)->startOfDay()->format($dateFormat);

        $this->branches = Branch::select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->customers = Customer::select('id', 'name')
            ->orderBy('name')
            ->get();

        $settings = LoyaltySetting::getForRestaurant(restaurant()->id);
        $this->valuePerPoint = (float) ($settings->value_per_point ?? 1);
    }

    public function updatedDateRangeType($value)
    {
        $this->setAsOfDate($value);
        $this->resetPage();
    }

    public function updatedAsOfDate()
    {
        if ($this->dateRangeType !== 'custom') {
            $this->dateRangeType = 'custom';
        }
        $this->resetPage();
    }

    public function updatedBranchId()
    {
        $this->resetPage();
    }

    public function updatedCustomerId()
    {
        $this->resetPage();
    }

    private function setAsOfDate(string $type): void
    {
        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $ranges = [
            'today' => Carbon::now($tz)->startOfDay(),
            'currentWeek' => Carbon::now($tz)->endOfWeek(),
            'lastWeek' => Carbon::now($tz)->subWeek()->endOfWeek(),
            'last7Days' => Carbon::now($tz)->startOfDay(),
            'currentMonth' => Carbon::now($tz)->startOfDay(),
            'lastMonth' => Carbon::now($tz)->subMonth()->endOfMonth(),
            'currentYear' => Carbon::now($tz)->startOfDay(),
            'lastYear' => Carbon::now($tz)->subYear()->endOfYear(),
            'custom' => Carbon::now($tz)->startOfDay(),
        ];

        $asOf = $ranges[$type] ?? $ranges['custom'];
        $this->asOfDate = $asOf->format($dateFormat);
    }

    public function getRowsProperty()
    {
        $timezone = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';
        $asOf = Carbon::createFromFormat($dateFormat, $this->asOfDate, $timezone)
            ->endOfDay()
            ->setTimezone('UTC')
            ->toDateTimeString();

        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;
        $customerId = $this->customerId !== 'all' ? (int) $this->customerId : null;

        $query = LoyaltyAccount::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('points_balance', '>', 0)
            ->select('loyalty_accounts.*');

        if ($branchId) {
            $query->whereHas('customer.orders', function ($orderQuery) use ($branchId, $asOf) {
                $orderQuery->where('branch_id', $branchId)
                    ->where('date_time', '<=', $asOf);
            });
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return $query->orderByDesc('points_balance')->paginate(25);
    }

    public function exportReport()
    {
        $locationLabel = __('loyalty::app.allLocations');
        if ($this->branchId !== 'all') {
            $branch = $this->branches->firstWhere('id', (int) $this->branchId);
            if ($branch) {
                $locationLabel = $branch->name;
            }
        }

        $dateRangeLabel = __('loyalty::app.asOfDate') . ': ' . $this->asOfDate;

        return Excel::download(
            new LoyaltyLiabilityExport(
                $this->buildQuery()->get(),
                $this->valuePerPoint,
                $this->asOfDate,
                __('loyalty::app.loyaltyLiabilityReport'),
                $locationLabel,
                $dateRangeLabel
            ),
            'loyalty-liability-' . now()->toDateTimeString() . '.xlsx'
        );
    }

    private function buildQuery()
    {
        $timezone = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';
        $asOf = Carbon::createFromFormat($dateFormat, $this->asOfDate, $timezone)
            ->endOfDay()
            ->setTimezone('UTC')
            ->toDateTimeString();

        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;
        $customerId = $this->customerId !== 'all' ? (int) $this->customerId : null;

        $query = LoyaltyAccount::query()
            ->where('restaurant_id', restaurant()->id)
            ->where('points_balance', '>', 0)
            ->select('loyalty_accounts.*')
            ->with('customer:id,name');

        if ($branchId) {
            $query->whereHas('customer.orders', function ($orderQuery) use ($branchId, $asOf) {
                $orderQuery->where('branch_id', $branchId)
                    ->where('date_time', '<=', $asOf);
            });
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return $query->orderByDesc('points_balance');
    }

    public function getTotalsProperty(): array
    {
        $rows = $this->rows;
        $totalPoints = $rows->sum('points_balance');
        $totalValue = round($totalPoints * $this->valuePerPoint, 2);

        return [
            'points' => $totalPoints,
            'value' => $totalValue,
        ];
    }

    public function render()
    {
        return view('loyalty::livewire.reports.loyalty-liability-report', [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'customers' => $this->customers,
        ]);
    }
}

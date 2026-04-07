<?php

namespace Modules\Loyalty\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Loyalty\Entities\LoyaltyLedger;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Exports\PointsLedgerExport;

class PointsLedgerReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'custom';
    public $startDate;
    public $endDate;
    public $branchId = 'all';
    public $transactionType = 'all';
    public $customerId = 'all';
    public $employeeId = 'all';
    public $branches = [];
    public $customers = [];
    public $employees = [];
    public $valuePerPoint = 1;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        abort_if(!function_exists('restaurant_modules') || !in_array('Loyalty', restaurant_modules()), 403);
        abort_if((!user_can('Show Reports')), 403);

        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $this->dateRangeType = 'custom';
        $end = Carbon::now($tz)->startOfDay();
        $start = $end->copy()->subMonth()->startOfDay();
        $this->startDate = $start->format($dateFormat);
        $this->endDate = $end->format($dateFormat);

        $this->branches = Branch::select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->customers = Customer::select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->employees = User::select('id', 'name')
            ->where(function ($query) {
                $query->where('restaurant_id', restaurant()->id)
                    ->orWhereNull('restaurant_id');
            })
            ->orderBy('name')
            ->get();

        $settings = LoyaltySetting::getForRestaurant(restaurant()->id);
        $this->valuePerPoint = (float) ($settings->value_per_point ?? 1);
    }

    public function updatedDateRangeType($value)
    {
        $this->setDateRange($value);
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        if ($this->dateRangeType !== 'custom') {
            $this->dateRangeType = 'custom';
        }
        $this->resetPage();
    }

    public function updatedEndDate()
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

    public function updatedTransactionType()
    {
        $this->resetPage();
    }

    public function updatedCustomerId()
    {
        $this->resetPage();
    }

    public function updatedEmployeeId()
    {
        $this->resetPage();
    }

    private function setDateRange(string $type): void
    {
        $tz = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $ranges = [
            'today' => [Carbon::now($tz)->startOfDay(), Carbon::now($tz)->startOfDay()],
            'lastWeek' => [Carbon::now($tz)->subWeek()->startOfWeek(), Carbon::now($tz)->subWeek()->endOfWeek()],
            'last7Days' => [Carbon::now($tz)->subDays(7), Carbon::now($tz)->startOfDay()],
            'currentMonth' => [Carbon::now($tz)->startOfMonth(), Carbon::now($tz)->startOfDay()],
            'lastMonth' => [Carbon::now($tz)->subMonth()->startOfMonth(), Carbon::now($tz)->subMonth()->endOfMonth()],
            'currentYear' => [Carbon::now($tz)->startOfYear(), Carbon::now($tz)->startOfDay()],
            'lastYear' => [Carbon::now($tz)->subYear()->startOfYear(), Carbon::now($tz)->subYear()->endOfYear()],
            'currentWeek' => [Carbon::now($tz)->startOfWeek(), Carbon::now($tz)->endOfWeek()],
            'custom' => [
                Carbon::now($tz)->startOfDay()->subMonth(),
                Carbon::now($tz)->startOfDay()
            ],
        ];

        [$start, $end] = $ranges[$type] ?? $ranges['custom'];
        $this->startDate = $start->format($dateFormat);
        $this->endDate = $end->format($dateFormat);
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

        $dateRangeLabel = $this->startDate . ' - ' . $this->endDate;

        return Excel::download(
            new PointsLedgerExport(
                $this->buildLedgerQuery(false)->get(),
                $this->valuePerPoint,
                __('loyalty::app.pointsLedgerReport'),
                $locationLabel,
                $dateRangeLabel
            ),
            'loyalty-points-ledger-' . now()->toDateTimeString() . '.xlsx'
        );
    }

    public function getLedgerProperty()
    {
        return $this->buildLedgerQuery()->paginate(25);
    }

    private function buildLedgerQuery(bool $paginate = true)
    {
        $timezone = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';
        $restaurantId = restaurant()->id;

        $startLocal = Carbon::createFromFormat($dateFormat, $this->startDate, $timezone)->startOfDay();
        $endLocal = Carbon::createFromFormat($dateFormat, $this->endDate, $timezone)->endOfDay();
        $startUtc = $startLocal->copy()->setTimezone('UTC')->toDateTimeString();
        $endUtc = $endLocal->copy()->setTimezone('UTC')->toDateTimeString();

        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;
        $customerId = $this->customerId !== 'all' ? (int) $this->customerId : null;
        $employeeId = $this->employeeId !== 'all' ? $this->employeeId : null;

        $query = LoyaltyLedger::query()
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->select('loyalty_ledger.*')
            ->selectRaw('SUM(points) OVER (PARTITION BY customer_id ORDER BY created_at, id) as balance_after')
            ->with([
                'customer:id,name,phone,phone_code',
                'order:id,order_number,branch_id,added_by,waiter_id',
                'order.addedBy:id,name',
                'order.waiter:id,name',
            ]);

        if ($branchId) {
            $query->whereHas('order', function ($orderQuery) use ($branchId) {
                $orderQuery->where('branch_id', $branchId);
            });
        }

        if ($this->transactionType !== 'all') {
            $query->where('type', $this->transactionType);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($employeeId === 'system') {
            $query->where(function ($systemQuery) {
                $systemQuery->whereDoesntHave('order')
                    ->orWhereHas('order', function ($orderQuery) {
                        $orderQuery->whereNull('added_by')
                            ->whereNull('waiter_id');
                    });
            });
        } elseif (is_numeric($employeeId)) {
            $employeeId = (int) $employeeId;
            $query->whereHas('order', function ($orderQuery) use ($employeeId) {
                $orderQuery->where('added_by', $employeeId)
                    ->orWhere('waiter_id', $employeeId);
            });
        }

        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    public function render()
    {
        return view('loyalty::livewire.reports.points-ledger-report', [
            'ledger' => $this->ledger,
            'valuePerPoint' => $this->valuePerPoint,
            'customers' => $this->customers,
            'employees' => $this->employees,
        ]);
    }
}

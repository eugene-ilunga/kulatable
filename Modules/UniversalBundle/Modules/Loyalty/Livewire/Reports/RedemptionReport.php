<?php

namespace Modules\Loyalty\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Loyalty\Exports\RedemptionReportExport;

class RedemptionReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'custom';
    public $startDate;
    public $endDate;
    public $branchId = 'all';
    public $customerId = 'all';
    public $employeeId = 'all';
    public $branches = [];
    public $customers = [];
    public $employees = [];

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

    private function dateRangeUtc(): array
    {
        $timezone = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';

        $startLocal = Carbon::createFromFormat($dateFormat, $this->startDate, $timezone)->startOfDay();
        $endLocal = Carbon::createFromFormat($dateFormat, $this->endDate, $timezone)->endOfDay();

        return [
            $startLocal->copy()->setTimezone('UTC')->toDateTimeString(),
            $endLocal->copy()->setTimezone('UTC')->toDateTimeString(),
        ];
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
            new RedemptionReportExport(
                $this->buildQuery()->get(),
                __('loyalty::app.redemptionReport'),
                $locationLabel,
                $dateRangeLabel
            ),
            'loyalty-redemption-report-' . now()->toDateTimeString() . '.xlsx'
        );
    }

    public function getRedemptionsProperty()
    {
        return $this->buildQuery()->paginate(25);
    }

    private function buildQuery()
    {
        [$startUtc, $endUtc] = $this->dateRangeUtc();
        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;
        $customerId = $this->customerId !== 'all' ? (int) $this->customerId : null;
        $employeeId = $this->employeeId !== 'all' ? $this->employeeId : null;

        $query = Order::query()
            ->whereBetween('date_time', [$startUtc, $endUtc])
            ->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('loyalty_ledger')
                    ->whereColumn('loyalty_ledger.order_id', 'orders.id')
                    ->where('loyalty_ledger.type', 'REDEEM')
                    ->where('loyalty_ledger.points', '<', 0);
            })
            ->select('orders.*')
            ->selectSub(function ($subQuery) {
                $subQuery->from('loyalty_ledger')
                    ->selectRaw('ABS(COALESCE(SUM(points), 0))')
                    ->whereColumn('loyalty_ledger.order_id', 'orders.id')
                    ->where('loyalty_ledger.type', 'REDEEM');
            }, 'ledger_points_redeemed')
            ->with([
                'customer:id,name,phone,phone_code',
                'branch:id,name',
                'addedBy:id,name',
                'waiter:id,name',
            ]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($employeeId === 'system') {
            $query->whereNull('added_by')
                ->whereNull('waiter_id');
        } elseif (is_numeric($employeeId)) {
            $employeeId = (int) $employeeId;
            $query->where(function ($subQuery) use ($employeeId) {
                $subQuery->where('added_by', $employeeId)
                    ->orWhere('waiter_id', $employeeId);
            });
        }

        return $query->orderBy('date_time', 'desc');
    }

    public function render()
    {
        return view('loyalty::livewire.reports.redemption-report', [
            'redemptions' => $this->redemptions,
            'customers' => $this->customers,
            'employees' => $this->employees,
        ]);
    }
}

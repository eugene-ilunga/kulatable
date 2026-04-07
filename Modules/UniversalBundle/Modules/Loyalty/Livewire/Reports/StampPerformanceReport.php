<?php

namespace Modules\Loyalty\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Loyalty\Exports\StampPerformanceExport;

class StampPerformanceReport extends Component
{
    use WithPagination;

    public $dateRangeType = 'custom';
    public $startDate;
    public $endDate;
    public $branchId = 'all';
    public $customerId = 'all';
    public $branches = [];
    public $customers = [];

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

    public function getRowsProperty()
    {
        [$startUtc, $endUtc] = $this->dateRangeUtc();
        $restaurantId = restaurant()->id;
        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;
        $customerId = $this->customerId !== 'all' ? (int) $this->customerId : null;

        $earnSub = DB::table('loyalty_stamp_transactions as t')
            ->select(
                't.stamp_rule_id',
                DB::raw('SUM(t.stamps) as stamps_issued'),
                DB::raw('COUNT(DISTINCT t.customer_id) as customers_enrolled')
            )
            ->where('t.type', 'EARN')
            ->whereBetween('t.created_at', [$startUtc, $endUtc]);

        $redeemSub = DB::table('loyalty_stamp_transactions as t')
            ->select(
                't.stamp_rule_id',
                DB::raw('SUM(ABS(t.stamps)) as stamps_redeemed')
            )
            ->where('t.type', 'REDEEM')
            ->whereBetween('t.created_at', [$startUtc, $endUtc]);

        if ($branchId) {
            $earnSub->join('orders as o', 'o.id', '=', 't.order_id')
                ->where('o.branch_id', $branchId);
            $redeemSub->join('orders as o', 'o.id', '=', 't.order_id')
                ->where('o.branch_id', $branchId);
        }

        if ($customerId) {
            $earnSub->where('t.customer_id', $customerId);
            $redeemSub->where('t.customer_id', $customerId);
        }

        $earnSub = $earnSub->groupBy('t.stamp_rule_id');
        $redeemSub = $redeemSub->groupBy('t.stamp_rule_id');

        $query = DB::table('loyalty_stamp_rules as r')
            ->leftJoin('menu_items as m', 'm.id', '=', 'r.menu_item_id')
            ->leftJoinSub($earnSub, 'earn', function ($join) {
                $join->on('earn.stamp_rule_id', '=', 'r.id');
            })
            ->leftJoinSub($redeemSub, 'redeem', function ($join) {
                $join->on('redeem.stamp_rule_id', '=', 'r.id');
            })
            ->where('r.restaurant_id', $restaurantId)
            ->select(
                'r.id',
                'r.stamps_required',
                'r.reward_type',
                'r.reward_value',
                'r.is_active',
                'm.item_name as campaign_name',
                DB::raw('COALESCE(earn.customers_enrolled, 0) as customers_enrolled'),
                DB::raw('COALESCE(earn.stamps_issued, 0) as stamps_issued'),
                DB::raw('COALESCE(redeem.stamps_redeemed, 0) as stamps_redeemed')
            )
            ->orderBy('campaign_name');

        return $query->paginate(25);
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
            new StampPerformanceExport(
                $this->buildQuery()->get(),
                __('loyalty::app.stampPerformanceReport'),
                $locationLabel,
                $dateRangeLabel
            ),
            'loyalty-stamp-performance-' . now()->toDateTimeString() . '.xlsx'
        );
    }

    private function buildQuery()
    {
        [$startUtc, $endUtc] = $this->dateRangeUtc();
        $restaurantId = restaurant()->id;
        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;
        $customerId = $this->customerId !== 'all' ? (int) $this->customerId : null;

        $earnSub = DB::table('loyalty_stamp_transactions as t')
            ->select(
                't.stamp_rule_id',
                DB::raw('SUM(t.stamps) as stamps_issued'),
                DB::raw('COUNT(DISTINCT t.customer_id) as customers_enrolled')
            )
            ->where('t.type', 'EARN')
            ->whereBetween('t.created_at', [$startUtc, $endUtc]);

        $redeemSub = DB::table('loyalty_stamp_transactions as t')
            ->select(
                't.stamp_rule_id',
                DB::raw('SUM(ABS(t.stamps)) as stamps_redeemed')
            )
            ->where('t.type', 'REDEEM')
            ->whereBetween('t.created_at', [$startUtc, $endUtc]);

        if ($branchId) {
            $earnSub->join('orders as o', 'o.id', '=', 't.order_id')
                ->where('o.branch_id', $branchId);
            $redeemSub->join('orders as o', 'o.id', '=', 't.order_id')
                ->where('o.branch_id', $branchId);
        }

        if ($customerId) {
            $earnSub->where('t.customer_id', $customerId);
            $redeemSub->where('t.customer_id', $customerId);
        }

        $earnSub = $earnSub->groupBy('t.stamp_rule_id');
        $redeemSub = $redeemSub->groupBy('t.stamp_rule_id');

        return DB::table('loyalty_stamp_rules as r')
            ->leftJoin('menu_items as m', 'm.id', '=', 'r.menu_item_id')
            ->leftJoinSub($earnSub, 'earn', function ($join) {
                $join->on('earn.stamp_rule_id', '=', 'r.id');
            })
            ->leftJoinSub($redeemSub, 'redeem', function ($join) {
                $join->on('redeem.stamp_rule_id', '=', 'r.id');
            })
            ->where('r.restaurant_id', $restaurantId)
            ->select(
                'r.id',
                'r.stamps_required',
                'r.reward_type',
                'r.reward_value',
                'r.is_active',
                'm.item_name as campaign_name',
                DB::raw('COALESCE(earn.customers_enrolled, 0) as customers_enrolled'),
                DB::raw('COALESCE(earn.stamps_issued, 0) as stamps_issued'),
                DB::raw('COALESCE(redeem.stamps_redeemed, 0) as stamps_redeemed')
            )
            ->orderBy('campaign_name');
    }

    public function render()
    {
        return view('loyalty::livewire.reports.stamp-performance-report', [
            'rows' => $this->rows,
            'customers' => $this->customers,
        ]);
    }
}

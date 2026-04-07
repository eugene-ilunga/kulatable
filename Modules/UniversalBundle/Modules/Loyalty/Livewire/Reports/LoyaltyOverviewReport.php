<?php

namespace Modules\Loyalty\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Entities\LoyaltyLedger;
use Modules\Loyalty\Entities\LoyaltyStampRule;
use Modules\Loyalty\Entities\LoyaltyStampTransaction;

class LoyaltyOverviewReport extends Component
{
    public $dateRangeType = 'custom';
    public $startDate;
    public $endDate;
    public $branchId = 'all';
    public $branches = [];

    public $stats = [
        'total_members' => 0,
        'active_members' => 0,
        'points_issued' => 0,
        'points_redeemed' => 0,
        'stamps_issued' => 0,
        'stamps_redeemed' => 0,
        'rewards_redeemed' => 0,
        'repeat_rate_members' => 0,
        'repeat_rate_non_members' => 0,
        'loyalty_revenue' => 0,
    ];

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

        $this->loadStats();
    }

    public function updatedDateRangeType($value)
    {
        $this->setDateRange($value);
        $this->loadStats();
    }

    public function updatedStartDate()
    {
        if ($this->dateRangeType !== 'custom') {
            $this->dateRangeType = 'custom';
        }
        $this->loadStats();
    }

    public function updatedEndDate()
    {
        if ($this->dateRangeType !== 'custom') {
            $this->dateRangeType = 'custom';
        }
        $this->loadStats();
    }

    public function updatedBranchId()
    {
        $this->loadStats();
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

    private function loadStats(): void
    {
        $timezone = timezone();
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';
        $restaurantId = restaurant()->id;

        $startLocal = Carbon::createFromFormat($dateFormat, $this->startDate, $timezone)->startOfDay();
        $endLocal = Carbon::createFromFormat($dateFormat, $this->endDate, $timezone)->endOfDay();
        $startUtc = $startLocal->copy()->setTimezone('UTC');
        $endUtc = $endLocal->copy()->setTimezone('UTC');

        $branchId = $this->branchId !== 'all' ? (int) $this->branchId : null;

        $ordersBase = Order::query()
            ->whereBetween('date_time', [$startUtc->toDateTimeString(), $endUtc->toDateTimeString()])
            ->where('status', 'paid')
            ->whereNotNull('orders.customer_id');

        if ($branchId) {
            $ordersBase->where('branch_id', $branchId);
        }

        $memberOrders = (clone $ordersBase)
            ->join('loyalty_accounts as la', function ($join) use ($restaurantId) {
                $join->on('orders.customer_id', '=', 'la.customer_id')
                    ->where('la.restaurant_id', '=', $restaurantId);
            })
            ->select('orders.customer_id', DB::raw('COUNT(*) as orders_count'))
            ->groupBy('orders.customer_id');

        $this->stats['total_members'] = DB::query()->fromSub($memberOrders, 'member_orders')->count();

        $activeEndLocal = $endLocal->copy();
        $activeStartLocal = $activeEndLocal->copy()->subDays(30)->startOfDay();
        $activeStartUtc = $activeStartLocal->copy()->setTimezone('UTC');
        $activeEndUtc = $activeEndLocal->copy()->setTimezone('UTC');

        $activeMemberOrders = Order::query()
            ->whereBetween('date_time', [$activeStartUtc->toDateTimeString(), $activeEndUtc->toDateTimeString()])
            ->where('status', 'paid')
            ->whereNotNull('orders.customer_id')
            ->join('loyalty_accounts as la', function ($join) use ($restaurantId) {
                $join->on('orders.customer_id', '=', 'la.customer_id')
                    ->where('la.restaurant_id', '=', $restaurantId);
            })
            ->select('orders.customer_id')
            ->distinct();

        if ($branchId) {
            $activeMemberOrders->where('orders.branch_id', $branchId);
        }

        $this->stats['active_members'] = $activeMemberOrders->count('orders.customer_id');

        $ledgerBase = LoyaltyLedger::query()
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startUtc->toDateTimeString(), $endUtc->toDateTimeString()]);

        if ($branchId) {
            $ledgerBase->whereHas('order', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        $this->stats['points_issued'] = (int) (clone $ledgerBase)
            ->where('type', 'EARN')
            ->where('points', '>', 0)
            ->sum('points');
        $pointsRedeemed = (int) (clone $ledgerBase)
            ->where('type', 'REDEEM')
            ->sum('points');
        $this->stats['points_redeemed'] = abs($pointsRedeemed);

        $stampBase = LoyaltyStampTransaction::query()
            ->where('restaurant_id', $restaurantId)
            ->whereBetween('created_at', [$startUtc->toDateTimeString(), $endUtc->toDateTimeString()]);

        if ($branchId) {
            $stampBase->whereHas('order', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        $this->stats['stamps_issued'] = (int) (clone $stampBase)
            ->where('type', 'EARN')
            ->where('stamps', '>', 0)
            ->sum('stamps');
        $stampsRedeemed = (int) (clone $stampBase)
            ->where('type', 'REDEEM')
            ->sum('stamps');
        $this->stats['stamps_redeemed'] = abs($stampsRedeemed);
        $this->stats['rewards_redeemed'] = 0;

        $redeemedByRule = (clone $stampBase)
            ->where('type', 'REDEEM')
            ->select('stamp_rule_id', DB::raw('SUM(ABS(stamps)) as stamps_redeemed'))
            ->groupBy('stamp_rule_id')
            ->get();

        if ($redeemedByRule->isNotEmpty()) {
            $rules = LoyaltyStampRule::whereIn('id', $redeemedByRule->pluck('stamp_rule_id')->filter()->all())
                ->pluck('stamps_required', 'id');

            foreach ($redeemedByRule as $row) {
                $ruleId = (int) ($row->stamp_rule_id ?? 0);
                if ($ruleId <= 0) {
                    continue;
                }
                $required = max(1, (int) ($rules[$ruleId] ?? 1));
                $redeemed = (int) ($row->stamps_redeemed ?? 0);
                $this->stats['rewards_redeemed'] += (int) floor($redeemed / $required);
            }
        }

        $memberRepeatTotal = DB::query()->fromSub($memberOrders, 'member_orders')->count();
        $memberRepeaters = DB::query()->fromSub($memberOrders, 'member_orders')
            ->where('orders_count', '>=', 2)
            ->count();
        $this->stats['repeat_rate_members'] = $memberRepeatTotal > 0
            ? round(($memberRepeaters / $memberRepeatTotal) * 100, 2)
            : 0;

        $nonMemberOrders = (clone $ordersBase)
            ->leftJoin('loyalty_accounts as la', function ($join) use ($restaurantId) {
                $join->on('orders.customer_id', '=', 'la.customer_id')
                    ->where('la.restaurant_id', '=', $restaurantId);
            })
            ->whereNull('la.id')
            ->select('orders.customer_id', DB::raw('COUNT(*) as orders_count'))
            ->groupBy('orders.customer_id');

        $nonMemberTotal = DB::query()->fromSub($nonMemberOrders, 'non_member_orders')->count();
        $nonMemberRepeaters = DB::query()->fromSub($nonMemberOrders, 'non_member_orders')
            ->where('orders_count', '>=', 2)
            ->count();
        $this->stats['repeat_rate_non_members'] = $nonMemberTotal > 0
            ? round(($nonMemberRepeaters / $nonMemberTotal) * 100, 2)
            : 0;

        $this->stats['loyalty_revenue'] = (float) (clone $ordersBase)
            ->join('loyalty_accounts as la', function ($join) use ($restaurantId) {
                $join->on('orders.customer_id', '=', 'la.customer_id')
                    ->where('la.restaurant_id', '=', $restaurantId);
            })
            ->sum('orders.total');
    }

    public function render()
    {
        return view('loyalty::livewire.reports.loyalty-overview-report');
    }
}

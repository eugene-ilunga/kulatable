<?php

namespace Modules\MultiPOS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MultiPOS\Entities\PosMachine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\MultiPOS\Exports\PosMachineReportExport;

class PosMachineReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(!in_array('MultiPOS', restaurant_modules()), 403);
            return $next($request);
        });
    }

    /**
     * Display POS machine sales summary report
     */
    public function salesSummary()
    {
        $isAdmin = user() && user()->hasRole('Admin_' . user()->restaurant_id);
        abort_if(!$isAdmin, 403);

        $currentBranch = branch();
        $branches = \App\Models\Branch::where('restaurant_id', $currentBranch->restaurant_id)->get();

        // Match Sales Report quick filters
        // Accept both our names and Sales Report's names for seamless UI reuse
        $dateRangeType = request('dateRangeType'); // from Sales Report
        $range = request('range'); // legacy param
        $startDate = request('start_date', request('start'));
        $endDate = request('end_date', request('end'));
        $startTime = request('start_time', '00:00');
        $endTime = request('end_time', '23:59');

        // Determine range like Sales Report
        if (!empty($dateRangeType) && $dateRangeType != 'custom') {
            $selectedRange = $dateRangeType;
        } elseif (!empty($startDate) || !empty($endDate)) {
            $selectedRange = 'custom';
        } else {
            $selectedRange = $range ?: 'currentWeek';
        }

        switch ($selectedRange) {
            case 'today':
                $startDate = now()->toDateString();
                $endDate = now()->toDateString();
                break;
            case 'yesterday':
                $startDate = now()->subDay()->toDateString();
                $endDate = now()->subDay()->toDateString();
                break;
            case 'currentWeek':
                $startDate = now()->startOfWeek()->toDateString();
                $endDate = now()->endOfWeek()->toDateString();
                break;
            case 'lastWeek':
                $startDate = now()->subWeek()->startOfWeek()->toDateString();
                $endDate = now()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'last7Days':
                $startDate = now()->subDays(6)->toDateString();
                $endDate = now()->toDateString();
                break;
            case 'currentMonth':
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->endOfMonth()->toDateString();
                break;
            case 'lastMonth':
                $startDate = now()->subMonth()->startOfMonth()->toDateString();
                $endDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'currentYear':
                $startDate = now()->startOfYear()->toDateString();
                $endDate = now()->endOfYear()->toDateString();
                break;
            case 'lastYear':
                $startDate = now()->subYear()->startOfYear()->toDateString();
                $endDate = now()->subYear()->endOfYear()->toDateString();
                break;
            case 'custom':
            default:
                $startDate = $startDate ?: now()->toDateString();
                $endDate = $endDate ?: now()->toDateString();
                break;
        }

        // Parse dates EXACTLY like inline report
        // Inline report uses: Carbon::parse(($startDate ?? now()->toDateString()).' '.($startTime ?? '00:00'))
        $startDateTime = \Carbon\Carbon::parse(($startDate ?? now()->toDateString()).' '.($startTime ?? '00:00'));
        $endDateTime = \Carbon\Carbon::parse(($endDate ?? now()->toDateString()).' '.($endTime ?? '23:59'));

        // Get machines for current branch
        $machines = PosMachine::where('branch_id', $currentBranch->id)
            ->active()
            ->get();

        // Get report data
        $reportData = $this->getMachineReportData($currentBranch->id, $startDateTime, $endDateTime);

        return view('multipos::reports.sales-summary', compact('reportData', 'machines', 'branches', 'currentBranch', 'startDate', 'endDate', 'startTime', 'endTime', 'selectedRange'));
    }

    /**
     * Get report data via AJAX (no page reload)
     */
    public function getReportData(Request $request)
    {
        $isAdmin = user() && user()->hasRole('Admin_' . user()->restaurant_id);
        abort_if(!$isAdmin, 403);

        $currentBranch = branch();

        // Match Sales Report quick filters
        $dateRangeType = $request->input('dateRangeType');
        $range = $request->input('range');
        $startDate = $request->input('start_date', $request->input('start'));
        $endDate = $request->input('end_date', $request->input('end'));
        $startTime = $request->input('start_time', '00:00');
        $endTime = $request->input('end_time', '23:59');

        // Determine range like Sales Report
        if (!empty($dateRangeType) && $dateRangeType != 'custom') {
            $selectedRange = $dateRangeType;
        } elseif (!empty($startDate) || !empty($endDate)) {
            $selectedRange = 'custom';
        } else {
            $selectedRange = $range ?: 'currentWeek';
        }

        switch ($selectedRange) {
            case 'today':
                $startDate = now()->toDateString();
                $endDate = now()->toDateString();
                break;
            case 'yesterday':
                $startDate = now()->subDay()->toDateString();
                $endDate = now()->subDay()->toDateString();
                break;
            case 'currentWeek':
                $startDate = now()->startOfWeek()->toDateString();
                $endDate = now()->endOfWeek()->toDateString();
                break;
            case 'lastWeek':
                $startDate = now()->subWeek()->startOfWeek()->toDateString();
                $endDate = now()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'last7Days':
                $startDate = now()->subDays(6)->toDateString();
                $endDate = now()->toDateString();
                break;
            case 'currentMonth':
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->endOfMonth()->toDateString();
                break;
            case 'lastMonth':
                $startDate = now()->subMonth()->startOfMonth()->toDateString();
                $endDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'currentYear':
                $startDate = now()->startOfYear()->toDateString();
                $endDate = now()->endOfYear()->toDateString();
                break;
            case 'lastYear':
                $startDate = now()->subYear()->startOfYear()->toDateString();
                $endDate = now()->subYear()->endOfYear()->toDateString();
                break;
            case 'custom':
            default:
                $startDate = $startDate ?: now()->toDateString();
                $endDate = $endDate ?: now()->toDateString();
                break;
        }

        // Parse dates EXACTLY like inline report
        $startDateTime = \Carbon\Carbon::parse(($startDate ?? now()->toDateString()).' '.($startTime ?? '00:00'));
        $endDateTime = \Carbon\Carbon::parse(($endDate ?? now()->toDateString()).' '.($endTime ?? '23:59'));

        // Get report data
        $reportData = $this->getMachineReportData($currentBranch->id, $startDateTime, $endDateTime);

        return response()->json([
            'reportData' => $reportData,
            'summary' => [
                'total_machines' => count($reportData),
                'total_orders' => $reportData->sum('total_orders'),
                'net_sales' => $reportData->sum('net_sales'),
                'avg_order_value' => $reportData->avg('avg_order_value'),
            ],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'selectedRange' => $selectedRange,
        ]);
    }

    /**
     * Get machine-specific report data
     */
    private function getMachineReportData($branchId, $startDateTime, $endDateTime)
    {
        // Root problem: Orders have pos_machine_id but pos_machines records don't exist
        // Solution: Match inline report EXACTLY, but add orders.pos_machine_id to SELECT/GROUP BY for fallback
        $primaryData = DB::table('orders')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->leftJoin('pos_machines', 'orders.pos_machine_id', '=', 'pos_machines.id')
            ->where('orders.branch_id', $branchId)
            ->whereBetween('orders.date_time', [$startDateTime, $endDateTime])
            ->whereIn('orders.status', ['paid', 'payment_due'])
            ->select(
                'orders.pos_machine_id as order_machine_id', // Keep for fallback
                'pos_machines.id as machine_id',
                'pos_machines.alias as machine_alias',
                'pos_machines.public_id as machine_public_id',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(payments.amount) as net_sales'),
                DB::raw('SUM(payments.amount) / NULLIF(COUNT(DISTINCT orders.id),0) as avg_order_value'),
                DB::raw('SUM(CASE WHEN payments.payment_method = "cash" THEN payments.amount ELSE 0 END) as cash_sales'),
                DB::raw('SUM(CASE WHEN payments.payment_method IN ("card", "upi", "stripe", "razorpay", "flutterwave", "paypal", "bank_transfer") THEN payments.amount ELSE 0 END) as card_upi_sales'),
                DB::raw('0 as refunds')
            )
            ->groupBy('orders.pos_machine_id', 'pos_machines.id', 'pos_machines.alias', 'pos_machines.public_id')
            ->orderBy('net_sales', 'desc')
            ->get();

        return $primaryData->map(function ($row) {
            // Use pos_machines.id if exists, otherwise fallback to orders.pos_machine_id
            $machineId = !is_null($row->machine_id) ? $row->machine_id : $row->order_machine_id;

            // If we have machine_id from pos_machines, use its alias, otherwise create one from order_machine_id
            $machineAlias = null;
            if (!empty($row->machine_alias)) {
                $machineAlias = $row->machine_alias;
            } elseif ($machineId) {
                $machineAlias = 'Machine #' . $machineId;
            } else {
                $machineAlias = 'Unnamed';
            }

            return [
                'machine_id' => $machineId,
                'machine_alias' => $machineAlias,
                'machine_public_id' => $row->machine_public_id ?? null,
                'total_orders' => (int) ($row->total_orders ?? 0),
                'net_sales' => (float) ($row->net_sales ?? 0),
                'avg_order_value' => (float) ($row->avg_order_value ?? 0),
                'cash_sales' => (float) ($row->cash_sales ?? 0),
                'card_upi_sales' => (float) ($row->card_upi_sales ?? 0),
                'refunds' => (float) ($row->refunds ?? 0),
            ];
        });
    }

    /**
     * Export report as CSV
     */
    public function exportCSV(Request $request)
    {
        $isAdmin = user() && user()->hasRole('Admin_' . user()->restaurant_id);
        abort_if(!$isAdmin, 403);

        $branchId = $request->input('branch_id', branch()->id);
        $dateRangeType = $request->input('dateRangeType');
        $range = $request->input('range');
        $startDate = $request->input('start_date', $request->input('start'));
        $endDate = $request->input('end_date', $request->input('end'));
        $startTime = $request->input('start_time', '00:00');
        $endTime = $request->input('end_time', '23:59');

        // Determine range like Sales Report
        // If dateRangeType is explicitly set and not 'custom', use it (ignore start/end dates)
        // Otherwise, if start/end dates are provided, use 'custom'
        // Default to 'currentWeek'
        if (!empty($dateRangeType) && $dateRangeType != 'custom') {
            $selectedRange = $dateRangeType;
        } elseif (!empty($startDate) || !empty($endDate)) {
            $selectedRange = 'custom';
        } else {
            $selectedRange = $range ?: 'currentWeek';
        }

        switch ($selectedRange) {
            case 'today':
                $startDate = now()->toDateString();
                $endDate = now()->toDateString();
                break;
            case 'yesterday':
                $startDate = now()->subDay()->toDateString();
                $endDate = now()->subDay()->toDateString();
                break;
            case 'currentWeek':
                $startDate = now()->startOfWeek()->toDateString();
                $endDate = now()->endOfWeek()->toDateString();
                break;
            case 'lastWeek':
                $startDate = now()->subWeek()->startOfWeek()->toDateString();
                $endDate = now()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'last7Days':
                $startDate = now()->subDays(6)->toDateString();
                $endDate = now()->toDateString();
                break;
            case 'currentMonth':
                $startDate = now()->startOfMonth()->toDateString();
                $endDate = now()->endOfMonth()->toDateString();
                break;
            case 'lastMonth':
                $startDate = now()->subMonth()->startOfMonth()->toDateString();
                $endDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'currentYear':
                $startDate = now()->startOfYear()->toDateString();
                $endDate = now()->endOfYear()->toDateString();
                break;
            case 'lastYear':
                $startDate = now()->subYear()->startOfYear()->toDateString();
                $endDate = now()->subYear()->endOfYear()->toDateString();
                break;
            case 'custom':
            default:
                $startDate = $startDate ?: now()->toDateString();
                $endDate = $endDate ?: now()->toDateString();
                break;
        }

        // Parse dates exactly like inline report - using restaurant timezone, NOT UTC
        $timezone = timezone(); // Get restaurant timezone
        $startDateTime = \Carbon\Carbon::parse($startDate.' '.$startTime, $timezone);
        $endDateTime = \Carbon\Carbon::parse($endDate.' '.$endTime, $timezone);

        $reportData = $this->getMachineReportData($branchId, $startDateTime, $endDateTime);

        $rows = collect($reportData)->map(function ($row) {
            return [
                $row['machine_public_id'],
                $row['machine_alias'],
                $row['total_orders'],
                round($row['net_sales'], 2),
                round($row['avg_order_value'], 2),
                round($row['cash_sales'], 2),
                round($row['card_upi_sales'], 2),
            ];
        })->toArray();

        $filename = 'pos-machine-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new PosMachineReportExport($rows), $filename);
    }
}

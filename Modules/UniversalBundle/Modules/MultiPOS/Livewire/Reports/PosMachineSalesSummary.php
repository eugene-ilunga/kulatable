<?php

namespace Modules\MultiPOS\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Modules\MultiPOS\Entities\PosMachine;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\MultiPOS\Exports\PosMachineReportExport;
use Livewire\Attributes\On;

#[Layout('layouts.app')]
class PosMachineSalesSummary extends Component
{
    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;
    public $startTime = '00:00';
    public $endTime = '23:59';
    public $reportData = [];
    public $currentBranch;
    public $branches = [];
    public $machines = [];

    public function mount()
    {
        abort_unless(in_array('MultiPOS', restaurant_modules()), 403);
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        $this->currentBranch = branch();
        $this->branches = \App\Models\Branch::where('restaurant_id', $this->currentBranch->restaurant_id)->get();
        $this->machines = PosMachine::where('branch_id', $this->currentBranch->id)->active()->get();

        // Initialize date range type from cookie if available
        $this->dateRangeType = request()->cookie('multipos_report_date_range_type', 'currentWeek');
        $this->setDateRange();
        $this->loadReportData();
    }

    public function setDateRange()
    {
        $timezone = timezone();
        $now = \Carbon\Carbon::now($timezone);
        
        $ranges = [
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'currentWeek' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'lastWeek' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'last7Days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'currentMonth' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'lastMonth' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'currentYear' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'lastYear' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
        ];

        if (isset($ranges[$this->dateRangeType])) {
            [$start, $end] = $ranges[$this->dateRangeType];
            $this->startDate = $start->format('m/d/Y');
            $this->endDate = $end->format('m/d/Y');
        } else {
            // Custom or default - keep existing format if already set, otherwise use current week
            if (!$this->startDate || !$this->endDate) {
                $this->startDate = $now->copy()->startOfWeek()->format('m/d/Y');
                $this->endDate = $now->copy()->endOfWeek()->format('m/d/Y');
            }
        }
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('multipos_report_date_range_type', $value, 60 * 24 * 30)); // 30 days
        $this->setDateRange();
        $this->loadReportData();
    }

    public function updatedStartDate()
    {
        $this->dateRangeType = 'custom';
        $this->loadReportData();
    }

    public function updatedEndDate()
    {
        $this->dateRangeType = 'custom';
        $this->loadReportData();
    }

    public function updatedStartTime()
    {
        $this->dateRangeType = 'custom';
        $this->loadReportData();
    }

    public function updatedEndTime()
    {
        $this->dateRangeType = 'custom';
        $this->loadReportData();
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
        $this->dateRangeType = 'custom';
        $this->loadReportData();
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
        $this->dateRangeType = 'custom';
        $this->loadReportData();
    }

    public function loadReportData()
    {
        $timezone = timezone();
        
        // Parse dates from m/d/Y format (from date picker) or Y-m-d format (fallback)
        $startDateStr = $this->startDate ?? now()->format('m/d/Y');
        $endDateStr = $this->endDate ?? now()->format('m/d/Y');

        // Try to parse as m/d/Y first, then fallback to Y-m-d
        try {
            $startDateTime = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $startDateStr . ' ' . ($this->startTime ?? '00:00'), $timezone);
        } catch (\Exception $e) {
            try {
                $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $startDateStr . ' ' . ($this->startTime ?? '00:00'), $timezone);
            } catch (\Exception $e2) {
                $startDateTime = \Carbon\Carbon::parse($startDateStr . ' ' . ($this->startTime ?? '00:00'), $timezone);
            }
        }

        try {
            $endDateTime = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $endDateStr . ' ' . ($this->endTime ?? '23:59'), $timezone);
        } catch (\Exception $e) {
            try {
                $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $endDateStr . ' ' . ($this->endTime ?? '23:59'), $timezone);
            } catch (\Exception $e2) {
                $endDateTime = \Carbon\Carbon::parse($endDateStr . ' ' . ($this->endTime ?? '23:59'), $timezone);
            }
        }

        // Convert to UTC for database queries (orders.date_time is stored in UTC)
        $startDateTime = $startDateTime->setTimezone('UTC');
        $endDateTime = $endDateTime->setTimezone('UTC');

        $this->reportData = $this->getMachineReportData($this->currentBranch->id, $startDateTime, $endDateTime)->toArray();
    }

    private function getMachineReportData($branchId, $startDateTime, $endDateTime)
    {
        $primaryData = DB::table('orders')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->join('pos_machines', 'orders.pos_machine_id', '=', 'pos_machines.id')
            ->where('orders.branch_id', $branchId)
            ->whereNotNull('orders.pos_machine_id')
            ->whereBetween('orders.date_time', [$startDateTime, $endDateTime])
            ->whereIn('orders.status', ['paid', 'payment_due'])
            ->select(
                'orders.pos_machine_id as order_machine_id',
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

        return $primaryData->filter(function ($row) {
            // Only include rows that have a valid machine_id
            return !is_null($row->machine_id) && !is_null($row->order_machine_id);
        })->map(function ($row) {
            $machineId = $row->machine_id;

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

    public function exportReport()
    {
        $timezone = timezone();
        
        // Parse dates from m/d/Y format (from date picker) or Y-m-d format (fallback)
        $startDateStr = $this->startDate ?? now()->format('m/d/Y');
        $endDateStr = $this->endDate ?? now()->format('m/d/Y');

        // Try to parse as m/d/Y first, then fallback to Y-m-d
        try {
            $startDateTime = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $startDateStr . ' ' . ($this->startTime ?? '00:00'), $timezone);
        } catch (\Exception $e) {
            try {
                $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $startDateStr . ' ' . ($this->startTime ?? '00:00'), $timezone);
            } catch (\Exception $e2) {
                $startDateTime = \Carbon\Carbon::parse($startDateStr . ' ' . ($this->startTime ?? '00:00'), $timezone);
            }
        }

        try {
            $endDateTime = \Carbon\Carbon::createFromFormat('m/d/Y H:i', $endDateStr . ' ' . ($this->endTime ?? '23:59'), $timezone);
        } catch (\Exception $e) {
            try {
                $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $endDateStr . ' ' . ($this->endTime ?? '23:59'), $timezone);
            } catch (\Exception $e2) {
                $endDateTime = \Carbon\Carbon::parse($endDateStr . ' ' . ($this->endTime ?? '23:59'), $timezone);
            }
        }

        // Convert to UTC for database queries (orders.date_time is stored in UTC)
        $startDateTime = $startDateTime->setTimezone('UTC');
        $endDateTime = $endDateTime->setTimezone('UTC');

        $reportData = $this->getMachineReportData($this->currentBranch->id, $startDateTime, $endDateTime);

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

    public function render()
    {
        return view('multipos::livewire.reports.pos-machine-sales-summary');
    }
}

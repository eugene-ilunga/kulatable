<?php

namespace Modules\CashRegister\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashRegisterController extends Controller
{

    public function __construct()
    {
        abort_if(!in_array('Cash Register', restaurant_modules()), 403);
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cashregister::index');
    }

    public function dashboard()
    {
        return view('cashregister::dashboard');
    }

    public function cashier()
    {
        abort_if(!(user_can('Manage Cash Register Settings') || user_can('Open Cash Register')), 403);
        return view('cashregister::cashier');
    }

    public function reports()
    {
        return view('cashregister::reports');
    }

    public function denominationsIndex()
    {

        return view('cashregister::denominations.index');
    }

    public function printThermalReport(\Illuminate\Http\Request $request)
    {

        try {
            $content = $request->input('content');
            $type = $request->input('type');
            $restaurantId = $request->input('restaurant_id');
            $branchId = $request->input('branch_id');

            // Find an available thermal printer for this branch
            $printer = \App\Models\Printer::where('restaurant_id', $restaurantId)
                ->where(function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                })
                ->where('print_format', 'like', 'thermal%')
                ->first();

            if (!$printer) {
                // Try to find any printer for this restaurant
                $printer = \App\Models\Printer::where('restaurant_id', $restaurantId)
                    ->first();
            }

            if (!$printer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No thermal printer configured for this branch/restaurant.'
                ]);
            }

            // Create print job
            $printJob = \App\Models\PrintJob::create([
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'printer_id' => $printer->id,
                'image_filename' => 'thermal_report_' . time() . '.html',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report sent to thermal printer successfully',
                'print_job_id' => $printJob->id
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to send report to printer: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cashregister::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cashregister::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cashregister::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}


    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportDiscrepancy(Request $request): StreamedResponse
    {

        $filename = 'discrepancy_report_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            // Header
            fputcsv($handle, ['Date', 'Branch', 'Cashier', 'Expected', 'Counted', 'Discrepancy', 'Status', 'Manager Note']);

            $component = app(\Modules\CashRegister\Livewire\Reports\DiscrepancyReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            // Permission-based scope: show all if allowed, else self-only
            $component->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
            $component->generateReport();

            foreach ($component->sessions as $s) {
                fputcsv($handle, [
                    optional($s->closed_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $s->branch->name ?? 'N/A',
                    $s->cashier->name ?? 'N/A',
                    $s->expected_cash,
                    $s->counted_cash,
                    $s->discrepancy,
                    $s->status,
                    $s->closing_reason,
                ]);
            }
        });
    }

    public function exportCashLedger(Request $request): StreamedResponse
    {

        $filename = 'cash_ledger_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            fputcsv($handle, ['Date', 'Branch', 'Cashier', 'Opening Float', 'Cash Sales', 'Cash In', 'Cash Out', 'Safe Drops', 'Expected', 'Counted', 'Discrepancy']);

            $component = app(\Modules\CashRegister\Livewire\Reports\CashLedgerReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            // Permission-based scope
            $component->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
            $component->generateReport();

            foreach ($component->sessions as $s) {
                $transactions = \Modules\CashRegister\Entities\CashRegisterTransaction::where('cash_register_session_id', $s->id)->get();
                $paymentMethodTotals = $transactions
                    ->whereIn('type', ['cash_sale', 'order_payment'])
                    ->groupBy(function ($transaction) {
                        return $transaction->payment_method ?: 'cash';
                    })
                    ->map(function ($items) {
                        return (float) $items->sum('amount');
                    })
                    ->toArray();
                $totalPayments = array_sum($paymentMethodTotals);
                $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
                $cashOut = $transactions->where('type', 'cash_out')->sum('amount');
                $safeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
                $changeGiven = $transactions->where('type', 'change_given')->sum('amount');
                $refunds = $transactions->where('type', 'refund')->sum('amount');
                $expectedCash = (float) $s->opening_float + $totalPayments + $cashIn - $changeGiven - $cashOut - $safeDrops - $refunds;
                $countedCash = (float) ($s->counted_cash ?? 0);
                $diff = $countedCash - $expectedCash;
                fputcsv($handle, [
                    optional($s->opened_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $s->branch->name ?? 'N/A',
                    $s->cashier->name ?? 'N/A',
                    $s->opening_float,
                    $transactions->where('type', 'cash_sale')->sum('amount'),
                    $cashIn,
                    $cashOut,
                    $safeDrops,
                    $expectedCash,
                    $countedCash,
                    $diff,
                ]);
            }
        });
    }

    public function exportCashInOut(Request $request): StreamedResponse
    {

        $filename = 'cash_in_out_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            fputcsv($handle, ['Date & Time', 'Branch', 'Cashier', 'Type', 'Amount', 'Reason']);

            $component = app(\Modules\CashRegister\Livewire\Reports\CashInOutReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            $component->registerId = $request->query('register');
            // Permission-based scope: if no permission, force to self
            $component->cashierId = user_can('View Cash Register Reports') ? $request->query('cashier') : user()->id;
            $component->type = $request->query('type');
            $component->generateReport();

            foreach ($component->transactions as $t) {
                if ($t->type === 'order_payment') {
                    $method = $t->payment_method ?: 'card';
                    $translated = __('modules.order.' . $method);
                    $methodLabel = $translated !== 'modules.order.' . $method
                        ? $translated
                        : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $method));
                    $typeLabel = $methodLabel;
                } elseif ($t->type === 'cash_sale') {
                    $typeLabel = __('cashregister::app.cashSalesLabel');
                } else {
                    // Match table label: translate app.<type>; fallback to Title Case if missing
                    $typeKey = 'app.' . $t->type;
                    $translated = __($typeKey);
                    $typeLabel = $translated !== $typeKey ? $translated : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $t->type));
                }

                $reasonLabel = $t->reason;
                if (!$reasonLabel && in_array($t->type, ['cash_sale', 'order_payment'], true)) {
                    $method = $t->payment_method ?: 'cash';
                    $translated = __('modules.order.' . $method);
                    $reasonLabel = $translated !== 'modules.order.' . $method
                        ? $translated
                        : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $method));
                }

                fputcsv($handle, [
                    optional($t->created_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $t->session->branch->name ?? 'N/A',
                    $t->session->cashier->name ?? 'N/A',
                    $typeLabel,
                    $t->amount,
                    $reasonLabel,
                ]);
            }
        });
    }

    public function exportSessionSummary(Request $request): StreamedResponse
    {

        $filename = 'session_summary_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            fputcsv($handle, ['Opened', 'Branch', 'Cashier', 'Session Type', 'Duration', 'Opening Float', 'Expected', 'Counted', 'Discrepancy', 'Status']);

            $component = app(\Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            // Permission-based scope
            $component->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
            $component->generateReport();

            foreach ($component->shifts as $s) {
                $transactions = \Modules\CashRegister\Entities\CashRegisterTransaction::where('cash_register_session_id', $s->id)->get();
                $paymentMethodTotals = $transactions
                    ->whereIn('type', ['cash_sale', 'order_payment'])
                    ->groupBy(function ($transaction) {
                        return $transaction->payment_method ?: 'cash';
                    })
                    ->map(function ($items) {
                        return (float) $items->sum('amount');
                    })
                    ->toArray();
                $totalPayments = array_sum($paymentMethodTotals);
                $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
                $cashOut = $transactions->where('type', 'cash_out')->sum('amount');
                $safeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
                $changeGiven = $transactions->where('type', 'change_given')->sum('amount');
                $refunds = $transactions->where('type', 'refund')->sum('amount');
                $expectedCash = (float) $s->opening_float + $totalPayments + $cashIn - $changeGiven - $cashOut - $safeDrops - $refunds;
                $countedCash = (float) ($s->counted_cash ?? 0);
                $diff = $countedCash - $expectedCash;
                fputcsv($handle, [
                    optional($s->opened_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $s->branch->name ?? 'N/A',
                    $s->cashier->name ?? 'N/A',
                    app(\Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class)->getSessionType($s),
                    app(\Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class)->getSessionDuration($s),
                    $s->opening_float,
                    $expectedCash,
                    $countedCash,
                    $diff,
                    $s->status,
                ]);
            }
        });
    }

    /**
     * Print cash register report (X-report or Z-report) for browser popup
     * Opens in browser for printing/saving as PDF
     * 
     * @param int $sessionId Cash register session ID
     * @param string $reportType 'x-report' or 'z-report'
     */
    public function printCashRegisterReport($sessionId, $reportType)
    {
        // Normalize report type
        $reportType = strtolower($reportType);
        
        if (!in_array($reportType, ['x-report', 'z-report'])) {
            abort(404, 'Invalid report type');
        }

        // Get printer settings for width
        $orderPlace = \App\Models\MultipleOrder::first();
        $printerSetting = $orderPlace?->printerSetting;
        $width = \App\Models\Printer::getPrintWidth($printerSetting);
        $thermal = true;

        // Try to get stored report content from cache (stored when image was saved)
        $cacheKey = 'report_content_' . $sessionId . '_' . $reportType;
        $storedContent = \Illuminate\Support\Facades\Cache::get($cacheKey);

        // If we have stored content, wrap it in a proper HTML document for browser printing
        if ($storedContent) {
            // Check if content already has full HTML structure
            if (stripos($storedContent, '<html') !== false && stripos($storedContent, '</html>') !== false) {
                // Content already has full HTML structure, just add print script
                $storedContent = str_replace('</body>', '<script>window.onload = function() { window.print(); };</script></body>', $storedContent);
                return response($storedContent)->header('Content-Type', 'text/html');
            } else {
                // Content is just inner HTML, wrap it in a full document
                $html = '<!DOCTYPE html>
<html lang="' . app()->getLocale() . '" dir="' . (isRtl() ? 'rtl' : 'ltr') . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . restaurant()->name . ' - ' . strtoupper($reportType) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "DejaVu Sans", Arial, sans-serif; }
        [dir="rtl"] { text-align: right; }
        [dir="ltr"] { text-align: left; }
        body { padding: 10px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    ' . $storedContent . '
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
                return response($html)->header('Content-Type', 'text/html');
            }
        }

        // Fallback: Generate report content from session if cache is empty
        $session = \Modules\CashRegister\Entities\CashRegisterSession::with(['branch', 'register', 'cashier'])
            ->findOrFail($sessionId);

        if ($reportType === 'x-report') {
            return $this->printXReport($sessionId, $width, $thermal);
        } else {
            return $this->printZReport($sessionId, $width, $thermal);
        }
    }

    /**
     * Print X-Report for browser popup
     */
    public function printXReport($sessionId, $width = 80, $thermal = false)
    {
        // Get the session data
        $session = \Modules\CashRegister\Entities\CashRegisterSession::with(['branch', 'register', 'cashier'])
            ->findOrFail($sessionId);

        $transactions = $session->transactions()->get();
        $paymentMethodTotals = $transactions
            ->whereIn('type', ['cash_sale', 'order_payment'])
            ->groupBy(function ($transaction) {
                return $transaction->payment_method ?: 'cash';
            })
            ->map(function ($items) {
                return (float) $items->sum('amount');
            })
            ->sortKeys()
            ->toArray();
        $totalPayments = array_sum($paymentMethodTotals);
        $changeGiven = $transactions->where('type', 'change_given')->sum('amount');
        $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
        $cashOut = $transactions->where('type', 'cash_out')->sum('amount');
        $safeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
        $refunds = $transactions->where('type', 'refund')->sum('amount');
        $expectedCash = (float) $session->opening_float + $totalPayments + $cashIn - $changeGiven - $cashOut - $safeDrops - $refunds;

        // Generate report data (similar to XReport Livewire component)
        $reportData = [
            'generated_at' => now(),
            'session' => $session,
            'opening_float' => $session->opening_float,
            'cash_sales' => $transactions->where('type', 'cash_sale')->sum('amount'),
            'payment_method_totals' => $paymentMethodTotals,
            'total_payments' => $totalPayments,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'safe_drops' => $safeDrops,
            'refunds' => $refunds,
            'expected_cash' => $expectedCash,
        ];

        $content = view('cashregister::print.x-report', compact('reportData', 'width', 'thermal'))->render();
        
        // Wrap in full HTML document with print script
        $html = '<!DOCTYPE html>
<html lang="' . app()->getLocale() . '" dir="' . (isRtl() ? 'rtl' : 'ltr') . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . restaurant()->name . ' - X-REPORT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "DejaVu Sans", Arial, sans-serif; }
        [dir="rtl"] { text-align: right; }
        [dir="ltr"] { text-align: left; }
        body { padding: 10px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    ' . $content . '
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
        
        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Print Z-Report for browser popup
     */
    public function printZReport($sessionId, $width = 80, $thermal = false)
    {
        // Get the session data
        $session = \Modules\CashRegister\Entities\CashRegisterSession::with(['branch', 'register', 'cashier'])
            ->findOrFail($sessionId);

        $transactions = $session->transactions()->get();
        $paymentMethodTotals = $transactions
            ->whereIn('type', ['cash_sale', 'order_payment'])
            ->groupBy(function ($transaction) {
                return $transaction->payment_method ?: 'cash';
            })
            ->map(function ($items) {
                return (float) $items->sum('amount');
            })
            ->sortKeys()
            ->toArray();
        $totalPayments = array_sum($paymentMethodTotals);
        $changeGiven = $transactions->where('type', 'change_given')->sum('amount');
        $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
        $cashOut = $transactions->where('type', 'cash_out')->sum('amount');
        $safeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
        $refunds = $transactions->where('type', 'refund')->sum('amount');
        $expectedCash = (float) $session->opening_float + $totalPayments + $cashIn - $changeGiven - $cashOut - $safeDrops - $refunds;

        // Generate report data (similar to ZReport Livewire component)
        $reportData = [
            'generated_at' => now(),
            'session' => $session,
            'opening_float' => $session->opening_float,
            'cash_sales' => $transactions->where('type', 'cash_sale')->sum('amount'),
            'payment_method_totals' => $paymentMethodTotals,
            'total_payments' => $totalPayments,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'safe_drops' => $safeDrops,
            'refunds' => $refunds,
            'expected_cash' => $expectedCash,
            'counted_cash' => $session->counted_cash,
            'discrepancy' => $session->discrepancy,
        ];

        // Get denominations for this session
        $denominations = \Modules\CashRegister\Entities\CashRegisterCount::with('denomination')
            ->where('cash_register_session_id', $sessionId)
            ->where('count', '>', 0)
            ->get();

        $content = view('cashregister::print.z-report', compact('reportData', 'width', 'thermal', 'denominations'))->render();
        
        // Wrap in full HTML document with print script
        $html = '<!DOCTYPE html>
<html lang="' . app()->getLocale() . '" dir="' . (isRtl() ? 'rtl' : 'ltr') . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . restaurant()->name . ' - Z-REPORT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "DejaVu Sans", Arial, sans-serif; }
        [dir="rtl"] { text-align: right; }
        [dir="ltr"] { text-align: left; }
        body { padding: 10px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    ' . $content . '
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
        
        return response($html)->header('Content-Type', 'text/html');
    }
}

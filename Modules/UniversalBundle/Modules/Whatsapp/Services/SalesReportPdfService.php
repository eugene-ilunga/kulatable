<?php

namespace Modules\Whatsapp\Services;

use App\Models\Restaurant;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helper\Files;

class SalesReportPdfService
{
    /**
     * Generate PDF for sales report.
     *
     * @param int $restaurantId
     * @param string $reportType daily_sales, weekly_sales, monthly_sales
     * @param Carbon $date
     * @return string|null Full path to generated PDF file, or null on failure
     */
    public function generateSalesReportPdf(int $restaurantId, string $reportType, Carbon $date): ?string
    {
        try {
            $restaurant = Restaurant::with(['currency', 'branches'])->find($restaurantId);
            
            if (!$restaurant) {
                Log::error('Sales Report PDF: Restaurant not found', ['restaurant_id' => $restaurantId]);
                return null;
            }

            // Determine date range based on report type
            $startDate = null;
            $endDate = null;
            $reportTitle = '';

            switch ($reportType) {
                case 'daily_sales':
                    $startDate = $date->copy()->startOfDay();
                    $endDate = $date->copy()->endOfDay();
                    $reportTitle = 'Daily Sales Report - ' . $date->format('d M, Y');
                    break;

                case 'weekly_sales':
                    $startDate = $date->copy()->startOfWeek();
                    $endDate = $date->copy()->endOfWeek();
                    $reportTitle = 'Weekly Sales Report - ' . $startDate->format('d M') . ' to ' . $endDate->format('d M, Y');
                    break;

                case 'monthly_sales':
                    $startDate = $date->copy()->startOfMonth();
                    $endDate = $date->copy()->endOfMonth();
                    $reportTitle = 'Monthly Sales Report - ' . $date->format('F Y');
                    break;

                default:
                    Log::error('Sales Report PDF: Invalid report type', ['report_type' => $reportType]);
                    return null;
            }

            // Get orders for the period
            $orders = Order::whereHas('branch', function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId);
                })
                ->whereBetween('date_time', [$startDate, $endDate])
                ->whereNotIn('status', ['draft', 'canceled'])
                ->with(['branch', 'orderType', 'customer'])
                ->get();

            // Calculate totals
            $totalOrders = $orders->count();
            $totalRevenue = $orders->sum(function($order) {
                return (float) ($order->total ?? $order->sub_total ?? 0);
            });
            $totalTax = $orders->sum('total_tax_amount') ?? 0;
            $totalDiscount = $orders->sum('discount_amount') ?? 0;
            $netRevenue = $totalRevenue - $totalDiscount;

            // Group by date for breakdown
            $ordersByDate = $orders->groupBy(function ($order) {
                return Carbon::parse($order->date_time)->format('Y-m-d');
            });

            $currency = $restaurant->currency->currency_symbol ?? '';

            // Get orders by status
            $ordersByStatus = $orders->groupBy('status')->map->count();
            
            // Generate PDF
            $pdf = Pdf::loadView('whatsapp::pdf.sales-report', [
                'restaurant' => $restaurant,
                'reportTitle' => $reportTitle,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalOrders' => $totalOrders,
                'totalRevenue' => $totalRevenue,
                'totalTax' => $totalTax,
                'totalDiscount' => $totalDiscount,
                'netRevenue' => $netRevenue,
                'ordersByDate' => $ordersByDate,
                'ordersByStatus' => $ordersByStatus,
                'currency' => $currency,
            ]);

            $pdf->setPaper('A4', 'portrait');

            // Generate unique filename
            $filename = 'sales-report-' . $reportType . '-' . $date->format('Y-m-d') . '-' . time() . '.pdf';
            $directory = storage_path('app/temp/whatsapp');
            
            // Ensure directory exists
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filePath = $directory . '/' . $filename;
            $pdf->save($filePath);

            Log::info('Sales Report PDF generated successfully', [
                'restaurant_id' => $restaurantId,
                'report_type' => $reportType,
                'file_path' => $filePath,
            ]);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Sales Report PDF generation failed: ' . $e->getMessage(), [
                'restaurant_id' => $restaurantId,
                'report_type' => $reportType,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}


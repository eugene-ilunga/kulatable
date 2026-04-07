<?php

namespace Modules\Whatsapp\Services;

use App\Models\Restaurant;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helper\Files;

class OperationsSummaryPdfService
{
    /**
     * Generate PDF for daily operations summary.
     *
     * @param int $restaurantId
     * @param Carbon $date
     * @return string|null Full path to generated PDF file, or null on failure
     */
    public function generateDailyOperationsSummaryPdf(int $restaurantId, Carbon $date): ?string
    {
        try {
            $restaurant = Restaurant::with(['currency', 'branches'])->find($restaurantId);
            
            if (!$restaurant) {
                Log::error('Operations Summary PDF: Restaurant not found', ['restaurant_id' => $restaurantId]);
                return null;
            }

            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            // Get orders for the day (only paid orders)
            $orders = Order::whereHas('branch', function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId);
                })
                ->whereBetween('date_time', [$startOfDay, $endOfDay])
                ->where('status', 'paid')
                ->with(['branch', 'orderType'])
                ->get();

            // Get reservations for the day
            $reservations = Reservation::whereHas('branch', function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId);
                })
                ->whereBetween('reservation_date_time', [$startOfDay, $endOfDay])
                ->with('branch')
                ->get();

            // Calculate statistics
            $totalOrders = $orders->count();
            $totalRevenue = $orders->sum(function($order) {
                return (float) ($order->total ?? $order->sub_total ?? 0);
            });
            $totalTax = $orders->sum('total_tax_amount') ?? 0;
            $totalDiscount = $orders->sum('discount_amount') ?? 0;
            $netRevenue = $totalRevenue - $totalDiscount;
            $totalReservations = $reservations->count();
            
            // Get staff count
            $staffCount = User::where('restaurant_id', $restaurantId)->count();
            
            // Get orders by status
            $ordersByStatus = $orders->groupBy('status')->map->count();
            
            // Get orders by branch
            $ordersByBranch = $orders->groupBy('branch_id')->map(function($branchOrders) {
                return [
                    'count' => $branchOrders->count(),
                    'revenue' => $branchOrders->sum(function($order) {
                        return (float) ($order->total ?? $order->sub_total ?? 0);
                    }),
                    'branch_name' => $branchOrders->first()->branch->name ?? __('whatsapp::app.notAvailable'),
                ];
            });

            // Get primary branch name (branch with most orders, or first branch)
            $primaryBranch = null;
            if ($ordersByBranch->isNotEmpty()) {
                $primaryBranch = $ordersByBranch->sortByDesc('count')->first()['branch_name'];
            } else {
                $primaryBranch = $restaurant->branches->first()->name ?? $restaurant->name;
            }

            // Use currency_format helper to properly format currency with symbol
            // This ensures proper UTF-8 encoding for currency symbols
            $currencyId = $restaurant->currency_id ?? null;
            $currencySymbol = $restaurant->currency->currency_symbol ?? '₹';
            
            // Format amounts using the currency_format helper function
            $formattedRevenue = currency_format($totalRevenue, $currencyId, true, false);
            $formattedTax = currency_format($totalTax, $currencyId, true, false);
            $formattedDiscount = currency_format($totalDiscount, $currencyId, true, false);
            $formattedNetRevenue = currency_format($netRevenue, $currencyId, true, false);

            // Prepare data for PDF
            $data = [
                'restaurant' => $restaurant,
                'branch_name' => $primaryBranch,
                'date' => $date,
                'formatted_date' => $date->format('d M, Y'),
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'formatted_revenue' => $formattedRevenue,
                'total_tax' => $totalTax,
                'formatted_tax' => $formattedTax,
                'total_discount' => $totalDiscount,
                'formatted_discount' => $formattedDiscount,
                'net_revenue' => $netRevenue,
                'formatted_net_revenue' => $formattedNetRevenue,
                'total_reservations' => $totalReservations,
                'staff_count' => $staffCount,
                'orders_by_status' => $ordersByStatus,
                'orders_by_branch' => $ordersByBranch,
                'currency' => $currencySymbol,
                'currency_id' => $currencyId,
            ];

            // Generate PDF with UTF-8 encoding options
            $pdf = Pdf::loadView('whatsapp::pdf.operations-summary', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Set DOMPDF options for proper UTF-8 encoding
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultFont', 'DejaVu Sans');

            // Save PDF to storage
            $filename = 'operations-summary-' . $restaurantId . '-' . $date->format('Y-m-d') . '.pdf';
            $directory = public_path(Files::UPLOAD_FOLDER . '/whatsapp/operations-summary');
            
            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $fullPath = $directory . '/' . $filename;
            $pdf->save($fullPath);

            Log::info('Operations Summary PDF generated', [
                'restaurant_id' => $restaurantId,
                'date' => $date->format('Y-m-d'),
                'file_path' => $fullPath,
            ]);

            return $fullPath;
        } catch (\Exception $e) {
            Log::error('Operations Summary PDF generation failed', [
                'restaurant_id' => $restaurantId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}


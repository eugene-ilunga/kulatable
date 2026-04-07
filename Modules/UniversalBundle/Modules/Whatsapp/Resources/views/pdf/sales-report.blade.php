<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('whatsapp::app.salesReport') }} - {{ $reportTitle }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #333;
            margin: 5px;
            padding: 5px;
            line-height: 1.3;
            background: #f8f9fa;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
            padding: 12px;
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 12px;
            font-weight: normal;
            color: #333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 9px;
            color: #333;
        }
        .stats {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .stats td {
            width: 33.33%;
            padding: 4px;
            vertical-align: top;
        }
        .stat-box {
            border: none;
            padding: 8px;
            text-align: center;
            border-radius: 4px;
            height: 45px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e5e7eb;
        }
        .stat-label {
            font-size: 7px;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }
        .stat-value {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.2;
        }
        .table-section {
            background: white;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            margin: 0 0 6px 0;
            padding: 4px 0;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .table th {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            background: #fafafa;
            color: #333;
        }
        .table tr:nth-child(even) td {
            background: #f3f4f6;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .footer {
            position: absolute;
            bottom: 10px;
            left: 5px;
            right: 5px;
            padding: 8px;
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            border-radius: 3px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .revenue-highlight {
            color: #333;
            font-weight: bold;
        }
        .count-highlight {
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $restaurant->name ?? __('whatsapp::app.restaurant') }}</h1>
        <h2>{{ $reportTitle }}</h2>
        <p>{{ __('whatsapp::app.generatedOn') }} {{ now()->setTimezone(config('app.timezone', 'UTC'))->format('d M, Y H:i:s T') }}</p>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="stat-box">
                    <div class="stat-label">{{ __('whatsapp::app.totalOrders') }}</div>
                    <div class="stat-value">{{ number_format($totalOrders) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <div class="stat-label">{{ __('whatsapp::app.totalRevenue') }}</div>
                    <div class="stat-value">{{ $currency }}{{ number_format($totalRevenue, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <div class="stat-label">{{ __('whatsapp::app.netRevenue') }}</div>
                    <div class="stat-value">{{ $currency }}{{ number_format($netRevenue, 2) }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="stat-box">
                    <div class="stat-label">{{ __('whatsapp::app.totalTax') }}</div>
                    <div class="stat-value">{{ $currency }}{{ number_format($totalTax, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <div class="stat-label">{{ __('whatsapp::app.totalDiscount') }}</div>
                    <div class="stat-value">{{ $currency }}{{ number_format($totalDiscount, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box">
                    <div class="stat-label">{{ __('whatsapp::app.averageOrder') }}</div>
                    <div class="stat-value">{{ $currency }}{{ $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 2) : '0.00' }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if(isset($ordersByDate) && $ordersByDate->isNotEmpty())
    <div class="table-section">
        <div class="section-title">{{ __('whatsapp::app.dailyBreakdown') }}</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40%;">{{ __('whatsapp::app.date') }}</th>
                    <th class="text-center" style="width: 30%;">{{ __('whatsapp::app.orders') }}</th>
                    <th class="text-left" style="width: 30%;">{{ __('whatsapp::app.revenue') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordersByDate as $date => $dateOrders)
                <tr>
                    <td><strong>{{ \Carbon\Carbon::parse($date)->format('d M, Y') }}</strong></td>
                    <td class="text-center">
                        <span class="count-highlight">{{ number_format($dateOrders->count()) }}</span>
                    </td>
                    <td class="text-left">
                        <span class="revenue-highlight">{{ $currency }}{{ number_format($dateOrders->sum(function($order) { return (float) ($order->total ?? $order->sub_total ?? 0); }), 2) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(isset($ordersByStatus) && $ordersByStatus->isNotEmpty())
    <div class="table-section">
        <div class="section-title">{{ __('whatsapp::app.ordersByStatus') }}</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60%;">{{ __('whatsapp::app.status') }}</th>
                    <th class="text-right" style="width: 40%;">{{ __('whatsapp::app.count') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordersByStatus as $status => $count)
                <tr>
                    <td><strong>{{ ucfirst(str_replace('_', ' ', $status)) }}</strong></td>
                    <td class="text-right">
                        <span class="count-highlight">{{ number_format($count) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>{{ __('whatsapp::app.automatedSalesReport') }} | {{ __('whatsapp::app.tabletrackRestaurantManagementSystem') }}</p>
    </div>
</body>
</html>
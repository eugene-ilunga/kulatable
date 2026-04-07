<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('whatsapp::app.dailyOperationsSummary') }} - {{ $formatted_date }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #333;
            margin: 5px;
            padding: 5px;
            line-height: 1.3;
            background: #f8f9fa;
            min-height: 100vh;
            position: relative;
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
        }
        .stat-box.blue,
        .stat-box.green,
        .stat-box.purple,
        .stat-box.orange,
        .stat-box.yellow,
        .stat-box.indigo {
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
        .table th:first-child {
            border-radius: 3px 0 0 0;
        }
        .table th:last-child {
            border-radius: 0 3px 0 0;
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
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-success {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #10b981;
        }
        .badge-warning {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #f59e0b;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
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
        <h1>{{ $restaurant->name }}</h1>
        <h2>{{ __('whatsapp::app.dailyOperationsSummary') }}</h2>
        <p>{{ $formatted_date }}</p>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="stat-box blue">
                    <div class="stat-label">{{ __('whatsapp::app.totalOrders') }}</div>
                    <div class="stat-value">{{ number_format($total_orders) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box green">
                    <div class="stat-label">{{ __('whatsapp::app.totalRevenue') }}</div>
                    <div class="stat-value">{{ $formatted_revenue }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box purple">
                    <div class="stat-label">{{ __('whatsapp::app.netRevenue') }}</div>
                    <div class="stat-value">{{ $formatted_net_revenue }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="stat-box orange">
                    <div class="stat-label">{{ __('whatsapp::app.reservations') }}</div>
                    <div class="stat-value">{{ number_format($total_reservations) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box yellow">
                    <div class="stat-label">{{ __('whatsapp::app.staffOnDuty') }}</div>
                    <div class="stat-value">{{ number_format($staff_count) }}</div>
                </div>
            </td>
            <td>
                <div class="stat-box indigo">
                    <div class="stat-label">{{ __('whatsapp::app.totalTax') }}</div>
                    <div class="stat-value">{{ $formatted_tax }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if($orders_by_status->count() > 0)
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
                @foreach($orders_by_status as $status => $count)
                <tr>
                    <td>
                        <span class="badge badge-{{ $status === 'paid' ? 'success' : ($status === 'pending' ? 'warning' : 'info') }}">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                    </td>
                    <td class="text-right">
                        <span class="count-highlight">{{ number_format($count) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($orders_by_branch->count() > 0)
    <div class="table-section">
        <div class="section-title">{{ __('whatsapp::app.ordersByBranch') }}</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40%;">{{ __('whatsapp::app.branchName') }}</th>
                    <th class="text-center" style="width: 30%;">{{ __('whatsapp::app.totalOrders') }}</th>
                    <th class="text-left" style="width: 30%;">{{ __('whatsapp::app.revenue') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders_by_branch as $branchData)
                <tr>
                    <td><strong>{{ $branchData['branch_name'] }}</strong></td>
                    <td class="text-center">
                        <span class="count-highlight">{{ number_format($branchData['count']) }}</span>
                    </td>
                    <td class="text-left">
                        <span class="revenue-highlight">{{ currency_format($branchData['revenue'], $currency_id ?? null, true, false) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>{{ __('whatsapp::app.generatedOn') }} {{ now()->setTimezone(config('app.timezone', 'UTC'))->format('d M, Y H:i:s T') }} | {{ __('whatsapp::app.automatedDailyOperationsSummaryReport') }}</p>
    </div>
</body>
</html>
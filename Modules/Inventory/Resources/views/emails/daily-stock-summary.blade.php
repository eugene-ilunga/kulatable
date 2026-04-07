<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ trans('inventory::modules.stock.daily_summary_title') }} - {{ $date->format('M d, Y') }}</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --background-subtle: #f9fafb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .highlight {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 12px;
            border-radius: 9999px;
            background-color: var(--background-subtle);
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background-color: var(--background-subtle);
            border-radius: 12px;
            padding: 16px 18px;
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
        }

        .section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .section-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 13px;
        }

        th {
            background-color: var(--background-subtle);
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 11px;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .footer {
            text-align: center;
            color: var(--text-secondary);
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            margin-top: 24px;
        }

        .muted {
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">
                {{ trans('inventory::modules.stock.daily_summary_title') }}
            </div>
            <div class="subtitle">
                {{ $restaurant->name ?? '' }}
                @if(!empty($restaurant->name))
                    &bull;
                @endif
                {{ $date->format('M d, Y') }}
            </div>
            <div class="highlight">
                {{ trans('inventory::modules.stock.daily_summary_badge', ['days' => $summary['warning_days'] ?? 3]) }}
            </div>
        </div>

        <div class="overview">
            <div class="stat-card">
                <div class="stat-label">{{ trans('inventory::modules.stock.lowStockItems') }}</div>
                <div class="stat-value">{{ $summary['totals']['low_stock'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ trans('inventory::modules.stock.outOfStock') }}</div>
                <div class="stat-value">{{ $summary['totals']['out_of_stock'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ trans('inventory::modules.stock.expiringBatchesLabel') }}</div>
                <div class="stat-value">{{ $summary['totals']['expiring_batches'] ?? 0 }}</div>
            </div>
        </div>

        @php
            $branchesSummary = $summary['branches'] ?? [];
        @endphp

        @forelse($branchesSummary as $branchSummary)
            @php
                $branch = $branchSummary['branch'] ?? null;
                $lowStock = collect($branchSummary['low_stock'] ?? []);
                $outOfStock = collect($branchSummary['out_of_stock'] ?? []);
                $expiringBatches = collect($branchSummary['expiring_batches'] ?? []);

                $hasAny = $lowStock->isNotEmpty() || $outOfStock->isNotEmpty() || $expiringBatches->isNotEmpty();
            @endphp

            @if($hasAny)
                <div class="section">
                    <div class="section-title">
                        {{ $branch?->name ?? trans('inventory::modules.stock.selectBranch') }}
                    </div>
                    <div class="section-subtitle">
                        {{ trans('inventory::modules.stock.branch_summary_subtitle') }}
                    </div>

                    @if($lowStock->isNotEmpty())
                        <div class="section-subtitle" style="margin-top: 8px; margin-bottom: 4px;">
                            <strong>{{ trans('inventory::modules.stock.lowStockItems') }}</strong>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ trans('inventory::modules.inventoryItem.name') }}</th>
                                    <th>{{ trans('inventory::modules.stock.currentStock') }}</th>
                                    <th>{{ trans('inventory::modules.stock.minStock') }}</th>
                                    <th>{{ trans('inventory::modules.itemCategory.itemCategoryName') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStock as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ (float) $item->current_stock }} {{ $item->unit?->name }}</td>
                                        <td>{{ (float) $item->threshold_quantity }}</td>
                                        <td>{{ $item->category?->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if($outOfStock->isNotEmpty())
                        <div class="section-subtitle" style="margin-top: 12px; margin-bottom: 4px;">
                            <strong>{{ trans('inventory::modules.stock.outOfStock') }}</strong>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ trans('inventory::modules.inventoryItem.name') }}</th>
                                    <th>{{ trans('inventory::modules.inventoryItem.category') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outOfStock as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category?->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if($expiringBatches->isNotEmpty())
                        <div class="section-subtitle" style="margin-top: 12px; margin-bottom: 4px;">
                            <strong>{{ trans('inventory::modules.stock.expiringBatchesLabel') }}</strong>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ trans('inventory::modules.batchRecipe.batchInventory') }}</th>
                                    <th>{{ trans('inventory::modules.batchRecipe.quantity') }}</th>
                                    <th>{{ trans('inventory::modules.stock.expirationDate') }}</th>
                                    <th>{{ trans('inventory::modules.stock.stockStatus') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expiringBatches as $batch)
                                    <tr>
                                        <td>{{ $batch->batchRecipe?->name ?? '-' }}</td>
                                        <td>{{ (float) $batch->remaining_quantity }}</td>
                                        <td>{{ optional($batch->expiry_date)->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge badge-warning">
                                                {{ trans('inventory::modules.stock.expiringSoonBadge') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        @empty
            <p class="muted">
                {{ trans('inventory::modules.stock.noStockItemsFound') }}
            </p>
        @endforelse

        <div class="footer">
            {{ trans('inventory::modules.stock.daily_summary_footer') }}
        </div>
    </div>
</body>
</html>


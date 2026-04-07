<div
    x-data="{
        scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (!container) return;
            const lastMsg = document.getElementById('last-assistant-message');
            if (lastMsg) {
                setTimeout(() => lastMsg.scrollIntoView({ behavior: 'smooth', block: 'end' }), 100);
            } else {
                setTimeout(() => container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' }), 100);
            }
        }
    }"
    @messages-updated.window="scrollToBottom()"
    @scroll-to-bottom.window="scrollToBottom()"
    x-init="
        $watch('$wire.messages', () => scrollToBottom());
        $watch('$wire.isLoading', (value) => { if (!value) setTimeout(() => scrollToBottom(), 300); });
    "
>
@if($accessDenied)
<div class="flex items-center justify-center h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">@lang('aitools::app.core.aiAssistantNotAvailable')</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $accessDeniedReason }}</p>
        @if(str_contains(strtolower($accessDeniedReason), 'not enabled'))
        <a href="{{ route('settings.index') }}?tab=ai" class="inline-flex items-center px-4 py-2 bg-skin-base text-white rounded-lg hover:bg-skin-base/[.8]">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            @lang('aitools::app.core.goToAiSettings')
        </a>
        @endif
    </div>
</div>
@else
<div class="flex h-[calc(100vh-100px)] bg-gray-50 dark:bg-gray-900 overflow-hidden">
    <!-- Left Sidebar - Conversations -->
    <div class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-screen">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h2 class="text-lg font-semibold dark:text-white">@lang('aitools::app.core.aiAssistant')</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                @if($remainingTokens > 0)
                    @lang('aitools::app.core.tokensRemaining', ['count' => number_format($remainingTokens)])
                @else
                    @lang('aitools::app.core.noTokensRemaining')
                @endif
            </p>
        </div>

        <div class="p-4 flex-shrink-0 space-y-2">
            <button
                type="button"
                wire:click="newConversation"
                wire:loading.attr="disabled"
                class="w-full text-white justify-center bg-skin-base hover:bg-skin-base/[.8] dark:bg-skin-base dark:hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm px-3 py-2 text-center"
            >
                <span wire:loading.remove wire:target="newConversation">@lang('aitools::app.core.newConversation')</span>
                <span wire:loading wire:target="newConversation">@lang('aitools::app.core.loading')</span>
            </button>
            <button
                type="button"
                wire:click="showCapabilities"
                class="w-full text-gray-700 dark:text-gray-300 justify-center bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 font-medium rounded-lg text-sm px-3 py-2 text-center flex items-center"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @lang('aitools::app.core.whatCanIAsk')
            </button>
        </div>

        <div class="flex-1 overflow-y-auto min-h-0">
            <div class="space-y-1 p-2">
                @foreach($conversations as $conv)
                <div
                    wire:key="conv-{{ $conv->id }}"
                    wire:click="selectConversation({{ $conv->id }})"
                    wire:loading.attr="disabled"
                    class="p-3 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 {{ $conversationId == $conv->id ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                >
                    <p class="text-sm font-medium dark:text-white truncate">{{ $conv->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $conv->created_at->format('M d, H:i') }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right Side - Chat -->
    <div class="flex-1 flex flex-col h-screen min-w-0 relative">
        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4 pb-24" id="messages-container">
            @if(empty($messages))
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">@lang('aitools::app.core.noMessages')</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">@lang('aitools::app.core.startConversation')</p>
                </div>
            </div>
            @else

                @foreach($messages as $index => $msg)
                @php
                    $isLastMessage = $index === count($messages) - 1;
                    $isLastAssistantMessage = $isLastMessage && $msg['role'] === 'assistant';
                @endphp
                <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }} mb-6" @if($isLastAssistantMessage) id="last-assistant-message" @endif>
                    <div class="max-w-4xl w-full {{ $msg['role'] === 'user' ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700' }} rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow">
                        @if($msg['role'] === 'assistant' && is_array($msg['content']))
                            <!-- AI Response with widgets -->
                            @if(isset($msg['content']['answer']))
                                <div class="mb-4">
                                    <p class="text-gray-700 dark:text-gray-200 leading-relaxed">{{ $msg['content']['answer'] }}</p>
                                </div>
                            @endif

                            @if(isset($msg['content']['widgets']) && !empty($msg['content']['widgets']))
                                <div class="space-y-4 mt-4">
                                    @php
                                        // First, check if AI provided highlight_card widgets
                                        $highlightCards = collect($msg['content']['widgets'] ?? [])->where('type', 'highlight_card');

                                        // If no highlight cards, try to calculate from table data
                                        $salesSummary = null;
                                        if ($highlightCards->isEmpty()) {
                                            $tableWidget = collect($msg['content']['widgets'] ?? [])->firstWhere('type', 'table');
                                            if ($tableWidget && !empty($tableWidget['data'])) {
                                                $tableData = $tableWidget['data'];

                                                // Check if this is sales_by_day data (has gross_sales, net_sales, orders_count)
                                                if (!empty($tableData) && isset($tableData[0]['gross_sales'])) {
                                                    $totalGross = 0;
                                                    $totalNet = 0;
                                                    $totalOrders = 0;

                                                    foreach ($tableData as $row) {
                                                        $totalGross += (float) ($row['gross_sales'] ?? 0);
                                                        $totalNet += (float) ($row['net_sales'] ?? 0);
                                                        $totalOrders += (int) ($row['orders_count'] ?? 0);
                                                    }

                                                    // Create highlight cards from sales data
                                                    $highlightCards = collect([
                                                        [
                                                            'type' => 'highlight_card',
                                                            'title' => 'Total Gross Sales',
                                                            'data' => ['value' => '$' . number_format($totalGross, 2)]
                                                        ],
                                                        [
                                                            'type' => 'highlight_card',
                                                            'title' => 'Total Net Sales',
                                                            'data' => ['value' => '$' . number_format($totalNet, 2)]
                                                        ],
                                                        [
                                                            'type' => 'highlight_card',
                                                            'title' => 'Total Orders',
                                                            'data' => ['value' => number_format($totalOrders)]
                                                        ]
                                                    ]);
                                                }
                                                // Check if this is orders data (has total, order_no, or any numeric field that could be total)
                                                elseif (!empty($tableData)) {
                                                    $firstRow = $tableData[0] ?? [];

                                                    // Try different possible field names for total/amount
                                                    $totalField = null;
                                                    $possibleFields = ['total', 'order_total', 'amount', 'TOTAL', 'order_amount', 'subtotal', 'grand_total'];

                                                    foreach ($possibleFields as $field) {
                                                        if (isset($firstRow[$field]) && (is_numeric($firstRow[$field]) || is_string($firstRow[$field]))) {
                                                            $totalField = $field;
                                                            break;
                                                        }
                                                    }

                                                    if ($totalField) {
                                                        $totalSales = 0;
                                                        foreach ($tableData as $row) {
                                                            $value = $row[$totalField] ?? 0;
                                                            // Handle string values like "$1,234.56" or "1234.56"
                                                            if (is_string($value)) {
                                                                $value = preg_replace('/[^0-9.]/', '', $value);
                                                            }
                                                            $totalSales += (float) $value;
                                                        }

                                                        $orderCount = count($tableData);

                                                        // Always create cards (even if totals are 0, user should see the summary)
                                                        // Create highlight cards from orders data
                                                        $highlightCards = collect([
                                                            [
                                                                'type' => 'highlight_card',
                                                                'title' => 'Total Sales',
                                                                'data' => ['value' => '$' . number_format($totalSales, 2)]
                                                            ],
                                                            [
                                                                'type' => 'highlight_card',
                                                                'title' => 'Total Orders',
                                                                'data' => ['value' => number_format($orderCount)]
                                                            ]
                                                        ]);
                                                    }
                                                }
                                            }
                                        }
                                    @endphp

                                    @if($highlightCards->isNotEmpty())
                                    <!-- Highlight Cards from AI or calculated -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        @foreach($highlightCards->take(3) as $card)
                                        @php
                                            $cardValue = $card['data']['value'] ?? $card['data']['total'] ?? '0';
                                            $cardTitle = $card['title'] ?? 'Metric';

                                            // Determine card color based on title
                                            $cardColors = [
                                                'Total Gross Sales' => 'from-green-500 to-green-600',
                                                'Total Net Sales' => 'from-blue-500 to-blue-600',
                                                'Total Sales' => 'from-green-500 to-green-600',
                                                'Total Orders' => 'from-purple-500 to-purple-600',
                                                'Total Revenue' => 'from-green-500 to-green-600',
                                            ];
                                            $bgColor = $cardColors[$cardTitle] ?? 'from-skin-base to-skin-base/[.9]';
                                        @endphp
                                        <div class="bg-skin-base rounded-xl p-5 shadow-lg text-white">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium opacity-90 mb-1">{{ $cardTitle }}</p>
                                                    <p class="text-2xl font-bold">{{ $cardValue }}</p>
                                                </div>
                                                <div class="bg-white/20 rounded-lg p-3">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif

                                    @foreach($msg['content']['widgets'] as $widget)
                                        @if($widget['type'] === 'highlight_card')
                                            {{-- Highlight cards are displayed above, skip here --}}
                                        @elseif($widget['type'] === 'ranked_metric')
                                            <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                                                <div class="flex items-center justify-between mb-4">
                                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                                        <svg class="w-5 h-5 mr-2 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                                        </svg>
                                                        {{ $widget['title'] ?? '' }}
                                                    </h4>
                                                </div>
                                                <div class="space-y-3">
                                                    @foreach(array_slice($widget['data'] ?? [], 0, 10) as $itemIndex => $item)

                                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                                                        <div class="flex-1">
                                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['name'] ?? $item['item_name'] ?? '' }}</span>
                                                            @if(isset($item['change']) || isset($item['percentage']))
                                                            <div class="flex items-center mt-1">
                                                                @php
                                                                    $change = floatval($item['change'] ?? $item['percentage'] ?? 0);
                                                                    $isPositive = $change >= 0;
                                                                @endphp
                                                                <span class="text-xs font-medium {{ $isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                                    {{ $isPositive ? '+' : '' }}{{ number_format($change, 1) }}%
                                                                </span>
                                                                @if($isPositive)
                                                                <svg class="w-3 h-3 ml-1 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                                </svg>
                                                                @else
                                                                <svg class="w-3 h-3 ml-1 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                </svg>
                                                                @endif
                                                            </div>
                                                            @endif
                                                        </div>
                                                        <div class="ml-4 text-right">
                                                            <span class="text-base font-semibold text-gray-900 dark:text-white">
                                                                @php
                                                                    $displayValue = '';

                                                                    // Helper function to parse currency strings
                                                                    $parseCurrency = function($val) {
                                                                        if (is_string($val)) {
                                                                            // Remove currency symbols and commas, then parse
                                                                            $cleaned = preg_replace('/[^0-9.]/', '', $val);
                                                                            return (float)$cleaned;
                                                                        }
                                                                        return (float)$val;
                                                                    };

                                                                    // Find the best numeric field to display (prioritize revenue-related fields)
                                                                    $revenueFields = ['revenue', 'total_revenue', 'amount', 'total_amount', 'sales', 'total_sales'];
                                                                    $otherNumericFields = ['value', 'qty_sold', 'quantity', 'count'];

                                                                    $foundValue = null;
                                                                    $foundValueName = null;

                                                                    // First, try revenue-related fields
                                                                    foreach ($revenueFields as $field) {
                                                                        if (isset($item[$field])) {
                                                                            $parsed = $parseCurrency($item[$field]);
                                                                            if ($parsed > 0) {
                                                                                $foundValue = $parsed;
                                                                                $foundValueName = $field;
                                                                                break;
                                                                            }
                                                                        }
                                                                    }

                                                                    // If no revenue field found, try other numeric fields
                                                                    if ($foundValue === null) {
                                                                        foreach ($otherNumericFields as $field) {
                                                                            if (isset($item[$field])) {
                                                                                $parsed = $parseCurrency($item[$field]);
                                                                                if ($parsed > 0) {
                                                                                    $foundValue = $parsed;
                                                                                    $foundValueName = $field;
                                                                                    break;
                                                                                }
                                                                            }
                                                                        }
                                                                    }

                                                                    // If still nothing, check all fields for numeric values
                                                                    if ($foundValue === null) {
                                                                        foreach ($item as $key => $val) {
                                                                            // Skip non-numeric fields
                                                                            if (in_array($key, ['name', 'item_name', 'title', 'id', 'item_id'])) {
                                                                                continue;
                                                                            }

                                                                            $parsed = $parseCurrency($val);
                                                                            if ($parsed > 0 && $parsed > ($foundValue ?? 0)) {
                                                                                $foundValue = $parsed;
                                                                                $foundValueName = $key;
                                                                            }
                                                                        }
                                                                    }

                                                                    // Format the display value
                                                                    if ($foundValue !== null && $foundValue > 0) {
                                                                        // If it's a revenue-related field or a large number (> 10), format as currency
                                                                        if (in_array($foundValueName, $revenueFields) || $foundValue >= 10) {
                                                                            $displayValue = '$' . number_format($foundValue, 2);
                                                                        } else {
                                                                            // For small numbers, might be quantity
                                                                            $displayValue = number_format((int)$foundValue);
                                                                        }
                                                                    } elseif (isset($item['revenue'])) {
                                                                        // Revenue exists but is 0, still show formatted
                                                                        $revenue = $parseCurrency($item['revenue']);
                                                                        $displayValue = '$' . number_format($revenue, 2);
                                                                    } elseif (isset($item['qty_sold'])) {
                                                                        // Show quantity as fallback
                                                                        $displayValue = number_format((int)$item['qty_sold']);
                                                                    }
                                                                @endphp
                                                                {{ $displayValue ?: '$0.00' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @elseif($widget['type'] === 'table')
                                            @php
                                                // Get consistent column order from first row
                                                $firstRow = $widget['data'][0] ?? [];
                                                $columnOrder = array_keys($firstRow);

                                                // Define preferred column order for orders table
                                                $preferredOrder = ['order_no', 'order_number', 'date', 'date_time', 'table', 'customer', 'total', 'status'];
                                                $orderedColumns = [];
                                                $remainingColumns = [];

                                                // Sort columns by preferred order
                                                foreach ($preferredOrder as $col) {
                                                    foreach ($columnOrder as $key) {
                                                        if (strtolower($key) === strtolower($col) && !in_array($key, $orderedColumns)) {
                                                            $orderedColumns[] = $key;
                                                            break;
                                                        }
                                                    }
                                                }

                                                // Add remaining columns
                                                foreach ($columnOrder as $key) {
                                                    if (!in_array($key, $orderedColumns)) {
                                                        $orderedColumns[] = $key;
                                                    }
                                                }

                                                // Use ordered columns or fallback to original order
                                                $displayColumns = !empty($orderedColumns) ? $orderedColumns : $columnOrder;
                                            @endphp
                                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                                            <tr>
                                                                @foreach($displayColumns as $header)
                                                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                                    {{ ucfirst(str_replace('_', ' ', $header)) }}
                                                                </th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                            @foreach(array_slice($widget['data'] ?? [], 0, 10) as $index => $row)
                                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900' }}">
                                                                @foreach($displayColumns as $key)
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                                    @php
                                                                        $cell = $row[$key] ?? null;
                                                                        // Format based on column type
                                                                        $keyLower = strtolower($key);

                                                                        // Handle order number fields - should be displayed as integer
                                                                        if (in_array($keyLower, ['order_no', 'order_number', 'order_num'])) {
                                                                            // Order number should be integer, remove decimals if present
                                                                            if (is_numeric($cell)) {
                                                                                $formatted = (string)(int)(float)$cell;
                                                                            } else {
                                                                                $formatted = $cell ?? '';
                                                                            }
                                                                        } elseif (in_array($keyLower, ['gross_sales', 'net_sales', 'revenue', 'total', 'tax_total', 'discount_total', 'amount', 'price'])) {
                                                                            // Currency fields - ensure we have a valid numeric value
                                                                            if ($cell === null || $cell === '') {
                                                                                $formatted = '$0.00';
                                                                            } else {
                                                                                $cellValue = is_string($cell) ? preg_replace('/[^0-9.-]/', '', $cell) : $cell;
                                                                                $cellValue = (float)$cellValue;
                                                                                $formatted = '$' . number_format($cellValue, 2);
                                                                            }
                                                                        } elseif (in_array($keyLower, ['date', 'date_time', 'reservation_date_time', 'created_at', 'completed_at', 'cancel_time', 'pickup_date'])) {
                                                                            // Date/DateTime fields - format with time if it's a datetime
                                                                            if ($cell === null || $cell === '') {
                                                                                $formatted = '';
                                                                            } else {
                                                                                try {
                                                                                    $date = \Carbon\Carbon::parse($cell);
                                                                                    // Check if it's a datetime (has time component) or just a date
                                                                                    if (in_array($keyLower, ['date_time', 'reservation_date_time', 'created_at', 'completed_at', 'cancel_time', 'pickup_date'])) {
                                                                                        // DateTime fields - show date and time
                                                                                        $formatted = $date->format('M d, Y h:i A');
                                                                                    } else {
                                                                                        // Date only fields
                                                                                        $formatted = $date->format('M d, Y');
                                                                                    }
                                                                                } catch (\Exception $e) {
                                                                                    $formatted = $cell;
                                                                                }
                                                                            }
                                                                        } elseif (in_array($keyLower, ['reservation_id', 'order_id', 'id'])) {
                                                                            // ID fields - format as integer without decimals
                                                                            $formatted = $cell !== null ? '#' . (int)$cell : '';
                                                                        } elseif (in_array($keyLower, ['orders_count', 'qty_sold', 'qty_used', 'quantity', 'count', 'party_size'])) {
                                                                            // Integer fields
                                                                            $formatted = $cell !== null ? number_format((int)$cell) : '0';
                                                                        } elseif (is_numeric($cell)) {
                                                                            // Other numeric fields
                                                                            $formatted = number_format((float)$cell, 2);
                                                                        } else {
                                                                            $formatted = $cell ?? '';
                                                                        }
                                                                    @endphp
                                                                    {{ $formatted }}
                                                                </td>
                                                                @endforeach
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @elseif($widget['type'] === 'highlight_card')
                                            <div class="bg-gradient-to-br from-skin-base to-skin-base/[.9] rounded-xl p-6 shadow-lg text-white">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h4 class="text-sm font-medium opacity-90 mb-1">{{ $widget['title'] ?? '' }}</h4>
                                                        <p class="text-3xl font-bold">{{ $widget['data']['value'] ?? '' }}</p>
                                                    </div>
                                                    <div class="bg-white/20 rounded-lg p-3">
                                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if(isset($msg['content']['followups']) && !empty($msg['content']['followups']))
                                <div class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wide">@lang('aitools::app.core.suggestedQuestions')</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($msg['content']['followups'] as $followup)
                                        <button
                                            wire:click="$set('message', '{{ addslashes($followup) }}')"
                                            class="text-xs px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 hover:shadow-sm transition-all duration-200 border border-gray-200 dark:border-gray-600"
                                        >
                                            {{ $followup }}
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            @if(is_array($msg['content']))
                                @if(isset($msg['content']['text']))
                                    <p>{{ $msg['content']['text'] }}</p>
                                @elseif(isset($msg['content']['answer']))
                                    <p>{{ $msg['content']['answer'] }}</p>
                                @else
                                    <p>{{ json_encode($msg['content'], JSON_PRETTY_PRINT) }}</p>
                                @endif
                            @else
                                <p>{{ $msg['content'] }}</p>
                            @endif
                        @endif
                        <p class="text-xs mt-2 opacity-70">{{ $msg['created_at'] }}</p>
                    </div>
                </div>
                @endforeach
            @endif

            @if($isLoading)
            <div class="flex justify-start mb-6">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-md max-w-4xl w-full">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="animate-spin rounded-full h-5 w-5 border-2 border-gray-300 border-t-skin-base"></div>
                                <div class="absolute inset-0 rounded-full border-2 border-skin-base/20"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">@lang('aitools::app.core.thinking')</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">@lang('aitools::app.core.processingRequest')</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span wire:poll.100ms="$refresh">{{ $startTime ? number_format($startTime->diffInMilliseconds(now()) / 1000, 1) : '0.0' }}s</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-skin-base rounded-full animate-pulse" style="width: 60%; animation: loading 1.5s ease-in-out infinite;"></div>
                        </div>
                        <div class="flex space-x-2">
                            <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full flex-1 animate-pulse" style="animation-delay: 0.1s;"></div>
                            <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full flex-1 animate-pulse" style="animation-delay: 0.2s;"></div>
                            <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full flex-1 animate-pulse" style="animation-delay: 0.3s;"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Input Area - Sticky at bottom -->
        <div class="sticky bottom-0 left-0 right-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 z-10 shadow-lg">
            <div class="flex space-x-4">
                <x-input
                    wire:model.live="message"
                    class="flex-1"
                    :placeholder="trans('aitools::app.core.askQuestion')"
                    :disabled="$isLoading"
                    wire:keydown.enter.prevent="sendMessage"
                    id="chat-input"
                />
                <button
                    type="button"
                    wire:click="sendMessage"
                    wire:target="sendMessage"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    @if($isLoading) disabled @endif
                    class="px-4 py-2 text-white bg-skin-base hover:bg-skin-base/[.8] dark:bg-skin-base dark:hover:bg-skin-base/[.8] font-semibold rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed transition-opacity cursor-pointer"
                    id="send-message-btn"
                >
                    <span wire:loading.remove wire:target="sendMessage">@lang('aitools::app.core.send')</span>
                    <span wire:loading wire:target="sendMessage" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        @lang('aitools::app.core.sending')
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    // Debug: Comprehensive button and Livewire check
    document.addEventListener('DOMContentLoaded', () => {
        console.log('=== AI Chat Debug Start ===');
        console.log('Livewire loaded:', typeof Livewire !== 'undefined');

        const sendButton = document.querySelector('#send-message-btn');
        console.log('Send button found:', !!sendButton);

        if (sendButton) {
            console.log('Button attributes:');
            console.log('  - wire:click:', sendButton.getAttribute('wire:click'));
            console.log('  - wire:target:', sendButton.getAttribute('wire:target'));
            console.log('  - disabled:', sendButton.disabled);
            console.log('  - type:', sendButton.type);

            // Test native click - but don't prevent Livewire from handling it
            sendButton.addEventListener('click', function(e) {
                console.log('=== Native click event fired ===');
                console.log('Event type:', e.type);
                console.log('Button disabled:', this.disabled);
                console.log('Livewire should handle this...');
                // Don't prevent default - let Livewire handle it
            }, false); // Use bubble phase, not capture
        } else {
            console.error('Send button NOT FOUND!');
        }
    });

    // Check after Livewire initializes
    document.addEventListener('livewire:init', () => {
        console.log('=== Livewire initialized ===');
        const sendButton = document.querySelector('#send-message-btn');
        if (sendButton) {
            console.log('Button after Livewire init');
            console.log('wire:click:', sendButton.getAttribute('wire:click'));
            console.log('Livewire version:', typeof Livewire !== 'undefined' ? (Livewire.version || 'unknown') : 'NOT FOUND');

            // Check if Livewire has processed this component
            const rootElement = sendButton.closest('[wire\\:id]');
            console.log('Root element with wire:id:', !!rootElement);
            if (rootElement) {
                const wireId = rootElement.getAttribute('wire:id');
                console.log('wire:id value:', wireId);
                const component = Livewire.find(wireId);
                console.log('Component found:', !!component);
                if (component) {
                    console.log('Component name:', component.name);
                    console.log('Component methods:', Object.keys(component));
                } else {
                    console.error('Component not found for wire:id:', wireId);
                    console.log('All Livewire components:', Object.keys(Livewire.all()));
                }
            } else {
                console.error('No root element with wire:id found!');
                console.log('Button parent elements:', sendButton.parentElement, sendButton.closest('div'));
            }
        }
    });

    // Listen for Livewire requests
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ component, respond }) => {
            console.log('=== Livewire request ===');
            console.log('Component:', component?.name);
            console.log('Method:', component?.$wire?.__instance?.lastMethod);
        });
    });

    // Auto-scroll function - improved version
    function scrollToBottom(force = false) {
        const container = document.getElementById('messages-container');
        if (!container) return;

        // Check if user has scrolled up (don't auto-scroll if they're reading old messages)
        const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 300;
        if (!force && !isNearBottom) {
            return; // User has scrolled up, don't auto-scroll
        }

        // Use multiple attempts to ensure scroll happens after DOM updates
        const attemptScroll = (delay = 0) => {
            setTimeout(() => {
                const lastAssistantMessage = document.getElementById('last-assistant-message');
                if (lastAssistantMessage) {
                    lastAssistantMessage.scrollIntoView({
                        behavior: 'smooth',
                        block: 'end',
                        inline: 'nearest'
                    });
                } else {
                    // Fallback to scrolling to bottom of container
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }, delay);
        };

        // Try multiple times with increasing delays to catch DOM updates
        attemptScroll(0);
        attemptScroll(100);
        attemptScroll(300);
        attemptScroll(500);
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('messages-container');
        if (container) {
            // Initial scroll to bottom
            setTimeout(() => scrollToBottom(true), 100);
        }
    });

    // Livewire event handlers - support both Livewire 2 and 3
    // Listen for browser events (Livewire 3 uses $this->dispatch() which creates browser events)
    window.addEventListener('messages-updated', () => {
        scrollToBottom(true);
    });

    window.addEventListener('scroll-to-bottom', () => {
        scrollToBottom(true);
    });

    document.addEventListener('livewire:init', () => {
        // Scroll on message sent
        if (typeof Livewire !== 'undefined') {
            // Livewire 2 uses Livewire.on
            if (typeof Livewire.on === 'function') {
                Livewire.on('message-sent', () => {
                    scrollToBottom(true);
                });

                Livewire.on('messages-updated', () => {
                    scrollToBottom(true);
                });

                Livewire.on('scroll-to-bottom', () => {
                    scrollToBottom(true);
                });
            }
        }

        // Hook into Livewire updates
        if (typeof Livewire !== 'undefined' && typeof Livewire.hook === 'function') {
            // Hook into morph updates
            Livewire.hook('morph.updated', ({ el, component }) => {
                const container = document.getElementById('messages-container');
                if (container && container.contains(el)) {
                    // Check if this is a message element
                    if (el.closest('#messages-container')) {
                        scrollToBottom();
                    }
                }
            });

            // Hook into component updates
            Livewire.hook('commit', ({ component, commit }) => {
                if (component && commit?.effects?.dirty) {
                    // Check if messages property was updated
                    const dirtyProps = commit.effects.dirty || [];
                    if (dirtyProps.includes('messages') || dirtyProps.some(p => p.includes('message'))) {
                        scrollToBottom(true);
                    }
                }
            });
        }
    });

    // Fallback for Livewire updates
    document.addEventListener('livewire:update', () => {
        scrollToBottom();
    });

    document.addEventListener('livewire:navigated', () => {
        setTimeout(() => scrollToBottom(true), 200);
    });

    // Watch for changes in messages container - most reliable method
    let scrollObserver = null;

    function setupScrollObserver() {
        const container = document.getElementById('messages-container');
        if (container && !scrollObserver) {
            scrollObserver = new MutationObserver((mutations) => {
                // Check if new content was added (new messages)
                const hasNewContent = mutations.some(mutation => {
                    if (mutation.addedNodes.length > 0) {
                        // Check if it's a message element or contains message content
                        return Array.from(mutation.addedNodes).some(node => {
                            if (node.nodeType === 1) { // Element node
                                return node.classList?.contains('mb-6') ||
                                       node.querySelector?.('.mb-6') ||
                                       node.id === 'last-assistant-message';
                            }
                            return false;
                        });
                    }
                    return false;
                });

                if (hasNewContent) {
                    // New message added, scroll to it
                    scrollToBottom(true);
                }
            });

            scrollObserver.observe(container, {
                childList: true,
                subtree: true,
                attributes: false,
                characterData: false
            });
        }
    }

    // Setup observer after DOM and Livewire are ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(setupScrollObserver, 300);
        });
    } else {
        setTimeout(setupScrollObserver, 300);
    }

    document.addEventListener('livewire:init', () => {
        setTimeout(setupScrollObserver, 500);
    });

    // Cleanup observer on page unload
    window.addEventListener('beforeunload', () => {
        if (scrollObserver) {
            scrollObserver.disconnect();
        }
    });
</script>

<style>
    @keyframes loading {
        0%, 100% {
            transform: translateX(-100%);
        }
        50% {
            transform: translateX(100%);
        }
    }
</style>

<!-- Capabilities Modal -->
@if($showCapabilitiesModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeCapabilities"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                        @lang('aitools::app.core.availableCapabilities')
                    </h3>
                    <button
                        type="button"
                        wire:click="closeCapabilities"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">@lang('aitools::app.core.capabilitiesDescription')</p>

                <div class="max-h-[60vh] overflow-y-auto pr-2">
                    <div class="space-y-6">
                        @if(!empty($capabilities))
                            @foreach($capabilities as $category => $tools)
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    {{ $category }}
                                </h4>
                                <div class="space-y-3">
                                    @foreach($tools as $tool)
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{{ $tool['description'] }}</p>
                                        @if(!empty($tool['examples']))
                                        <div class="mt-3">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">@lang('aitools::app.core.exampleQuestions'):</p>
                                            <ul class="space-y-1">
                                                @foreach($tool['examples'] as $example)
                                                <li class="text-xs text-gray-600 dark:text-gray-400 flex items-start">
                                                    <svg class="w-3 h-3 mr-1.5 mt-0.5 text-skin-base flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span>"{{ $example }}"</span>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">@lang('aitools::app.core.noCapabilitiesAvailable')</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                    type="button"
                    wire:click="closeCapabilities"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-skin-base text-base font-medium text-white hover:bg-skin-base/[.8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-skin-base sm:ml-3 sm:w-auto sm:text-sm"
                >
                    @lang('app.close')
                </button>
            </div>
        </div>
    </div>
</div>
@endif
</div>

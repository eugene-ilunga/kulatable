<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ restaurant()->name }} - X-Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        [dir="rtl"] {
            text-align: right;
        }

        [dir="ltr"] {
            text-align: left;
        }

        .receipt {
            width: {{ ($width ?? 80) - 5 }}mm;
            padding: {{ $thermal ? '1mm' : '6.35mm' }};
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .restaurant-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .report-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .info-section {
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-section table td {
            padding: 0;
            margin: 0;
            vertical-align: top;
        }

        .info-section table td:first-child {
            text-align: left;
        }

        .info-section table td:last-child {
            text-align: right;
        }

        [dir="rtl"] .info-section table td:first-child {
            text-align: right;
        }

        [dir="rtl"] .info-section table td:last-child {
            text-align: left;
        }

        .financial-section {
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .financial-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .financial-section table td {
            padding: 0;
            margin: 0;
            vertical-align: top;
            padding-bottom: 1mm;
        }

        .financial-section table td:first-child {
            text-align: left;
        }

        .financial-section table td:last-child {
            text-align: right;
        }

        [dir="rtl"] .financial-section table td:first-child {
            text-align: right;
        }

        [dir="rtl"] .financial-section table td:last-child {
            text-align: left;
        }

        .financial-line {
            margin-bottom: 1mm;
        }

        .financial-line table {
            width: 100%;
            border-collapse: collapse;
        }

        .financial-line table td {
            padding: 0;
            margin: 0;
            vertical-align: top;
        }

        .financial-line table td:first-child {
            text-align: left;
        }

        .financial-line table td:last-child {
            text-align: right;
        }

        [dir="rtl"] .financial-line table td:first-child {
            text-align: right;
        }

        [dir="rtl"] .financial-line table td:last-child {
            text-align: left;
        }

        .total-line {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 1mm;
            margin-top: 1mm;
            font-size: 11pt;
        }

        .total-line table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-line table td {
            padding: 0;
            margin: 0;
            vertical-align: top;
        }

        .total-line table td:first-child {
            text-align: left;
        }

        .total-line table td:last-child {
            text-align: right;
        }

        [dir="rtl"] .total-line table td:first-child {
            text-align: right;
        }

        [dir="rtl"] .total-line table td:last-child {
            text-align: left;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 9pt;
            padding-top: 2mm;
            border-top: 1px dashed #000;
            padding-bottom: 5mm;
        }

        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
        }
    </style>
</head>

<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="restaurant-name">{{ restaurant()->name }}</div>
            <div class="report-title">@lang('cashregister::app.xReport')</div>
        </div>

        <div class="separator"></div>

        <!-- Report Information -->
        <div class="info-section">
            <table>
                <tr>
                    <td>@lang('cashregister::app.generatedOn')</td>
                    <td>{{ $reportData['generated_at']->timezone(timezone())->format(dateFormat() . ' ' . timeFormat()) }}</td>
                </tr>
                <tr>
                    <td>@lang('cashregister::app.branch')</td>
                    <td>{{ $reportData['session']->branch->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>@lang('cashregister::app.register')</td>
                    <td>{{ $reportData['session']->register->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>@lang('cashregister::app.cashier')</td>
                    <td>{{ $reportData['session']->cashier->name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="separator"></div>

        <!-- Financial Data -->
        <div class="financial-section">
            <table>
                <tr>
                    <td>@lang('cashregister::app.openingFloat')</td>
                    <td>{{ currency_format($reportData['opening_float'], restaurant()->currency_id) }}</td>
                </tr>
                <tr>
                    <td>@lang('cashregister::app.cashSales')</td>
                    <td>{{ currency_format($reportData['cash_sales'], restaurant()->currency_id) }}</td>
                </tr>
                @if(!empty($reportData['payment_method_totals']))
                    @foreach($reportData['payment_method_totals'] as $method => $amount)
                        @continue($method === 'cash')
                        <tr>
                            <td>{{ __('modules.order.' . $method) !== 'modules.order.' . $method ? __('modules.order.' . $method) : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $method)) }}</td>
                            <td>{{ currency_format((float) $amount, restaurant()->currency_id) }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td>@lang('cashregister::app.cashIn')</td>
                    <td>{{ currency_format($reportData['cash_in'], restaurant()->currency_id) }}</td>
                </tr>
                <tr>
                    <td>@lang('cashregister::app.cashOut')</td>
                    <td>{{ currency_format($reportData['cash_out'], restaurant()->currency_id) }}</td>
                </tr>
                <tr>
                    <td>@lang('cashregister::app.safeDrops')</td>
                    <td>{{ currency_format($reportData['safe_drops'], restaurant()->currency_id) }}</td>
                </tr>
            </table>
        </div>

        <!-- Expected Cash Total -->
        <div class="financial-line total-line">
            <table>
                <tr>
                    <td>@lang('cashregister::app.expectedCash')</td>
                    <td>{{ currency_format($reportData['expected_cash'], restaurant()->currency_id) }}</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>@lang('cashregister::app.thankYou')</div>
        </div>
    </div>
    <script>
        function isPWA() {
            return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        }


        // On print dialog close, close window or navigate away
        function finishAfterPrint() {
            if (isPWA()) {
                goBack();
            } else {
                // Attempt to close. On some browsers, close() only works if window is opened by JS
                window.close();
                // If close fails (e.g. not opened by JS), just navigate away as fallback
                setTimeout(function() {
                    // Check if window is still open (only relevant outside PWA)
                    try {
                        if (!window.closed) {
                            window.location.href = '{{ route("cashregister.reports") }}';
                        }
                    } catch (e) {}
                }, 500);
            }
        }

        window.onafterprint = finishAfterPrint;

        // For browsers that do not support onafterprint or if it doesn't fire (esp. on mobile)
        function fallbackFinishHandler() {
            // Only trigger if print dialog does not close the window in time
            setTimeout(() => {
                // Avoid multiple prompts/redirects if already handled
                if (!window._printFinishedHandled) {
                    window._printFinishedHandled = true;
                    if(confirm("Did you finish printing? Click OK to go back.")) {
                        finishAfterPrint();
                    }
                }
            }, 1500); // Start fallback sooner to catch dialog cancel, but give time for onafterprint first
        }

        // Attempt automatic print on load
        window.onload = function() {
            if (isPWA()) {
                // Show custom back button if you have it, ignored if missing
                var btn = document.getElementById('backButton');
                if (btn) btn.style.display = 'block';
            }

            setTimeout(() => {
                try {
                    window.print();
                    fallbackFinishHandler();
                } catch (e) {
                    fallbackFinishHandler();
                }
            }, 500); // Faster popup
        };

        // Attempt print if user interacts and it's not already printed
        window.addEventListener('click', function() {
            if (!window._printFinishedHandled) {
                window.print();
                fallbackFinishHandler();
            }
        }, { once: true });

        // Additional fallback: if page becomes visible again after print dialog, treat as after print (covers some mobile cases)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible' && !window._printFinishedHandled) {
                // Some browsers hide page during print dialog, so this is after print/cancel
                setTimeout(() => {
                    if (!window._printFinishedHandled) {
                        window._printFinishedHandled = true;
                        finishAfterPrint();
                    }
                }, 200);
            }
        });
    </script>
</body>

</html>

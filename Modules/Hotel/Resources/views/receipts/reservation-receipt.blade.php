<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('hotel::modules.reservation.reservationReceiptTitle') }}</title>
    <style>
        body { font-family: DejaVu Sans, DejaVuSans, sans-serif; font-size: 12px; color: #111827; }
        .container { padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .title { font-size: 18px; font-weight: 700; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; text-align: left; font-weight: 700; }
        .right { text-align: right; }
        .small { font-size: 11px; }
        .totals td { border-bottom: none; padding: 6px; }
        .totals .label { color: #374151; }
        .totals .value { text-align: right; font-weight: 700; }
        .line { height: 1px; background: #e5e7eb; margin: 10px 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; border: 1px solid #e5e7eb; }
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        @page { margin: 14px 10px; }
    </style>
</head>
<body>
    <div class="container">
        @php
            $restaurant = $reservation->restaurant ?? (function_exists('restaurant') ? restaurant() : null);
            $branch = $reservation->branch ?? (function_exists('branch') ? branch() : null);
            $statusLabel = optional($reservation->status)->label() ?? (string) ($reservation->status->value ?? $reservation->status);
        @endphp

        <div class="header">
            <div>
                <div class="title">{{ $restaurant?->name ?? 'Hotel' }}</div>
                <div class="muted small mt-8">
                    @if($branch?->name) {{ $branch->name }} @endif
                </div>
                <div class="muted small mt-8">
                    {{ __('hotel::modules.reservation.reservationLabel') }}: <strong>{{ $reservation->reservation_number }}</strong>
                </div>
                <div class="muted small mt-8">
                    {{ __('hotel::modules.reservation.statusLabel') }}: <span class="badge">{{ $statusLabel }}</span>
                </div>
            </div>

            <div class="small right">
                <div class="muted">{{ __('hotel::modules.reservation.date') }}</div>
                <div>{{ optional($reservation->check_in_date)->format('d M Y') }}</div>
                <div class="muted mt-8">{{ __('hotel::modules.reservation.guest') }}</div>
                <div>{{ $reservation->primaryGuest?->full_name ?? '' }}</div>
            </div>
        </div>

        <div class="box mt-12">
            <div class="small muted">{{ __('hotel::modules.reservation.stay') }}</div>
            <div class="mt-8">
                {{ __('hotel::modules.reservation.checkIn') }}:
                <strong>{{ optional($reservation->check_in_date)->format('D, M d, Y') }}</strong>
                @if($reservation->check_in_time) · <strong>{{ \Carbon\Carbon::parse($reservation->check_in_time)->format('H:i') }}</strong> @endif
            </div>
            <div class="mt-8">
                {{ __('hotel::modules.reservation.checkOut') }}:
                <strong>{{ optional($reservation->check_out_date)->format('D, M d, Y') }}</strong>
                @if($reservation->check_out_time) · <strong>{{ \Carbon\Carbon::parse($reservation->check_out_time)->format('H:i') }}</strong> @endif
            </div>
        </div>

        <div class="box mt-12">
            <div class="small muted">{{ __('hotel::modules.reservation.rooms') }}</div>
            <div class="mt-8">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('hotel::modules.reservation.roomType') }}</th>
                            <th class="right">{{ __('hotel::modules.reservation.quantityShort') }}</th>
                            <th class="right">{{ __('hotel::modules.reservation.rate') }}</th>
                            <th class="right">{{ __('hotel::modules.reservation.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservation->reservationRooms as $rr)
                            <tr>
                                <td>{{ $rr->roomType?->name ?? '-' }}</td>
                                <td class="right">{{ $rr->quantity ?? 0 }}</td>
                                <td class="right">{{ currency_format($rr->rate ?? 0) }}</td>
                                <td class="right">{{ currency_format($rr->total_amount ?? 0) }}</td>
                            </tr>
                        @endforeach
                        @if($reservation->reservationRooms->isEmpty())
                            <tr>
                                <td colspan="4" class="muted small">{{ __('hotel::modules.reservation.noRoomsSelected') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if($reservation->reservationExtras->count())
            <div class="box mt-12">
                <div class="small muted">{{ __('hotel::modules.reservation.extras') }}</div>
                <div class="mt-8">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('hotel::modules.reservation.extras') }}</th>
                                <th class="right">{{ __('hotel::modules.reservation.quantityShort') }}</th>
                                <th class="right">{{ __('hotel::modules.reservation.unitPrice') }}</th>
                                <th class="right">{{ __('hotel::modules.reservation.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservation->reservationExtras as $re)
                                <tr>
                                    <td>{{ $re->extraService?->name ?? '-' }}</td>
                                    <td class="right">{{ $re->quantity ?? 0 }}</td>
                                    <td class="right">{{ currency_format($re->unit_price ?? 0) }}</td>
                                    <td class="right">{{ currency_format($re->total_amount ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="box mt-12">
            <div class="small muted">{{ __('hotel::modules.reservation.summaryLabel') }}</div>
            <div class="line"></div>
            <table class="totals">
                <tr>
                    <td class="label">{{ __('hotel::modules.reservation.roomsAndExtras') }}</td>
                    <td class="value">{{ currency_format($grossSubtotal) }}</td>
                </tr>
                <tr>
                    <td class="label">
                        {{ __('hotel::modules.reservation.discount') }}
                        @if(!empty($reservation->discount_type) && ($reservation->discount_value ?? 0) > 0)
                            <span class="muted small">({{ $reservation->discount_type }}: {{ $reservation->discount_value }})</span>
                        @endif
                    </td>
                    <td class="value">- {{ currency_format($discountAmount) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('hotel::modules.reservation.amountAfterDiscountBeforeTax') }}</td>
                    <td class="value">{{ currency_format($netAfterDiscount) }}</td>
                </tr>
                <tr>
                    <td class="label">
                        {{ __('hotel::modules.reservation.bookingTax') }}
                        @if($reservation->tax?->rate !== null)
                            <span class="muted small">({{ $reservation->tax?->rate }}%)</span>
                        @endif
                    </td>
                    <td class="value">{{ currency_format($reservation->tax_amount ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('hotel::modules.reservation.total') }}</td>
                    <td class="value">{{ currency_format($reservation->total_amount ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('hotel::modules.reservation.advancePaid') }}</td>
                    <td class="value">{{ currency_format($advancePaid) }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('hotel::modules.reservation.balanceDue') }}</td>
                    <td class="value">{{ currency_format($balanceDue) }}</td>
                </tr>
            </table>
        </div>

        <div class="muted small mt-12">
            {{ __('hotel::modules.reservation.generatedOn', ['datetime' => now()->format('d M Y H:i')]) }}
        </div>
    </div>
</body>
</html>


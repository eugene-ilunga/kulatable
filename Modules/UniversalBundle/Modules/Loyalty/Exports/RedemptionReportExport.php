<?php

namespace Modules\Loyalty\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RedemptionReportExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    public function __construct(
        protected Collection $orders,
        protected string $reportName,
        protected string $locationLabel,
        protected string $dateRangeLabel
    ) {}

    public function collection()
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            __('loyalty::app.orderId'),
            __('loyalty::app.customer'),
            __('loyalty::app.pointsRedeemed'),
            __('loyalty::app.redemptionValue'),
            __('loyalty::app.billBeforeRedemption'),
            __('loyalty::app.billAfterRedemption'),
            __('loyalty::app.paymentMethod'),
            __('loyalty::app.employee'),
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->mergeCells("A3:{$lastColumn}3");

                $sheet->setCellValue('A1', $this->reportName);
                $sheet->setCellValue('A2', $this->locationLabel);
                $sheet->setCellValue('A3', $this->dateRangeLabel);

                $sheet->getStyle('A1:A3')->getFont()->setBold(true);
            },
        ];
    }

    public function map($order): array
    {
        $orderNumber = $order->order_number ? '#' . $order->order_number : __('loyalty::app.notApplicable');
        $billAfter = (float) ($order->total ?? 0);
        $billBefore = $billAfter + (float) ($order->loyalty_discount_amount ?? 0);
        $employeeName = $order->addedBy?->name ?? $order->waiter?->name ?? __('loyalty::app.system');
        $methodKey = $order->payment_method ?? 'cash';
        $paymentLabel = __('modules.order.' . $methodKey);
        if ($paymentLabel === 'modules.order.' . $methodKey) {
            $paymentLabel = $methodKey;
        }

        return [
            $orderNumber,
            $order->customer?->name ?? __('loyalty::app.unknownCustomer'),
            (int) ($order->loyalty_points_redeemed ?? 0),
            (float) ($order->loyalty_discount_amount ?? 0),
            $billBefore,
            $billAfter,
            $paymentLabel,
            $employeeName,
        ];
    }
}

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

class PointsLedgerExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    public function __construct(
        protected Collection $entries,
        protected float $valuePerPoint,
        protected string $reportName,
        protected string $locationLabel,
        protected string $dateRangeLabel
    ) {}

    public function collection()
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            __('loyalty::app.transactionDate'),
            __('loyalty::app.customer'),
            __('loyalty::app.orderId'),
            __('loyalty::app.transactionType'),
            __('loyalty::app.pointsIn'),
            __('loyalty::app.pointsOut'),
            __('loyalty::app.balanceAfter'),
            __('loyalty::app.pointsValue'),
            __('loyalty::app.source'),
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

    public function map($entry): array
    {
        $pointsIn = $entry->points > 0 ? $entry->points : 0;
        $pointsOut = $entry->points < 0 ? abs($entry->points) : 0;
        $source = $entry->order_id ? __('loyalty::app.sourceOrder') : ($entry->type === 'EXPIRE' ? __('loyalty::app.sourceSystem') : __('loyalty::app.sourceAdmin'));
        $employeeName = $entry->order?->addedBy?->name ?? $entry->order?->waiter?->name ?? __('loyalty::app.system');
        $dateFormat = restaurant()->date_format ?? 'd-m-Y';
        $timeFormat = restaurant()->time_format ?? timeFormat();
        $displayDate = optional($entry->created_at)->timezone(timezone())->translatedFormat($dateFormat . ' ' . $timeFormat);
        $pointsValue = round(((int) $entry->points) * $this->valuePerPoint, 2);

        return [
            $displayDate,
            $entry->customer?->name ?? __('loyalty::app.unknownCustomer'),
            $entry->order?->order_number ? '#' . $entry->order->order_number : __('loyalty::app.notApplicable'),
            __('loyalty::app.' . strtolower($entry->type)),
            $pointsIn,
            $pointsOut,
            (int) ($entry->balance_after ?? 0),
            $pointsValue,
            $source,
            $employeeName,
        ];
    }
}

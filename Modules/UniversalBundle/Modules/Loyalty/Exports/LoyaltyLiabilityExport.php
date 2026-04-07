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

class LoyaltyLiabilityExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    public function __construct(
        protected Collection $rows,
        protected float $valuePerPoint,
        protected string $asOfDate,
        protected string $reportName,
        protected string $locationLabel,
        protected string $dateRangeLabel
    ) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            __('loyalty::app.customer'),
            __('loyalty::app.outstandingPoints'),
            __('loyalty::app.outstandingValue'),
            __('loyalty::app.asOfDate'),
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

    public function map($row): array
    {
        $value = round(((int) $row->points_balance) * $this->valuePerPoint, 2);

        return [
            $row->customer?->name ?? __('loyalty::app.unknownCustomer'),
            (int) $row->points_balance,
            $value,
            $this->asOfDate,
        ];
    }
}

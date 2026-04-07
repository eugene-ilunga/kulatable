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

class StampPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    public function __construct(
        protected Collection $rows,
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
            __('loyalty::app.stampCampaign'),
            __('loyalty::app.customersEnrolled'),
            __('loyalty::app.stampsIssued'),
            __('loyalty::app.cardsCompleted'),
            __('loyalty::app.rewardsIssued'),
            __('loyalty::app.completionRate'),
            __('loyalty::app.dropOffRate'),
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
        $stampsRequired = max(1, (int) ($row->stamps_required ?? 1));
        $stampsRedeemed = (int) ($row->stamps_redeemed ?? 0);
        $cardsCompleted = (int) floor($stampsRedeemed / $stampsRequired);
        $rewardsIssued = $cardsCompleted;
        $customersEnrolled = (int) ($row->customers_enrolled ?? 0);
        $rawCompletion = $customersEnrolled > 0 ? (($cardsCompleted / $customersEnrolled) * 100) : 0;
        $completionRate = round(min(100, max(0, $rawCompletion)), 2);
        $dropOffRate = round(max(0, 100 - $completionRate), 2);

        return [
            $row->campaign_name ?? __('loyalty::app.notApplicable'),
            (int) $customersEnrolled,
            (int) ($row->stamps_issued ?? 0),
            (int) $cardsCompleted,
            (int) $rewardsIssued,
            $completionRate . '%',
            $dropOffRate . '%',
        ];
    }
}

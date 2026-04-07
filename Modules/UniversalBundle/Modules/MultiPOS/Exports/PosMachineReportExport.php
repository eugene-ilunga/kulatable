<?php

namespace Modules\MultiPOS\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PosMachineReportExport implements FromArray, WithHeadings
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Machine ID',
            'Machine Alias',
            'Total Orders',
            'Net Sales',
            'Avg Order Value',
            'Cash Sales',
            'Card/UPI Sales',
        ];
    }
}



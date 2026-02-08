<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private Collection $invoices
    ) {}

    public function collection(): Collection
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Client',
            'Client Email',
            'Status',
            'Currency',
            'Subtotal',
            'Tax %',
            'Discount',
            'Total',
            'Due Date',
            'Created At',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->client?->name ?? '',
            $invoice->client?->email ?? '',
            $invoice->status?->label() ?? $invoice->status,
            $invoice->currency ?? 'USD',
            number_format((float) $invoice->subtotal, 2, '.', ''),
            number_format((float) $invoice->tax_percent, 2, '.', ''),
            number_format((float) $invoice->discount, 2, '.', ''),
            number_format((float) $invoice->total, 2, '.', ''),
            $invoice->due_date?->format('Y-m-d') ?? '',
            $invoice->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

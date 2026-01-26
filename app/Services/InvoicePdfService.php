<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfService
{
    public function generate(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['client', 'items.product', 'user.companySettings']);

        $data = [
            'invoice' => $invoice,
            'company' => $invoice->user->companySettings,
            'client' => $invoice->client,
            'items' => $invoice->items,
        ];

        return Pdf::loadView('pdf.invoice', $data)
            ->setPaper('a4', 'portrait');
    }

    public function download(Invoice $invoice): Response
    {
        $pdf = $this->generate($invoice);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function stream(Invoice $invoice): Response
    {
        $pdf = $this->generate($invoice);

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }
}

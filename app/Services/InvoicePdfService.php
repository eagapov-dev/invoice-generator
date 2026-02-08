<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfService
{
    public function __construct(
        private PlanLimitService $planLimitService
    ) {}

    public function generate(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['client', 'items.product', 'user.companySettings']);

        $template = $invoice->pdf_template ?? 'classic';
        $showWatermark = $this->planLimitService->shouldShowWatermark($invoice->user);

        $data = [
            'invoice' => $invoice,
            'company' => $invoice->user->companySettings,
            'client' => $invoice->client,
            'items' => $invoice->items,
            'showWatermark' => $showWatermark,
        ];

        $view = "pdf.invoice-{$template}";

        if (! view()->exists($view)) {
            $view = 'pdf.invoice-classic';
        }

        return Pdf::loadView($view, $data)
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PublicInvoiceController extends Controller
{
    public function __construct(
        private InvoicePdfService $pdfService
    ) {}

    public function show(string $token): JsonResponse
    {
        $invoice = Invoice::where('public_token', $token)
            ->with(['client', 'items', 'user.companySettings'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status?->value,
                'status_label' => $invoice->status?->label(),
                'currency' => $invoice->currency ?? 'USD',
                'subtotal' => (float) $invoice->subtotal,
                'tax_percent' => (float) $invoice->tax_percent,
                'discount' => (float) $invoice->discount,
                'total' => (float) $invoice->total,
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'notes' => $invoice->notes,
                'created_at' => $invoice->created_at->toISOString(),
                'company' => [
                    'name' => $invoice->user->companySettings?->company_name ?? 'Company',
                    'address' => $invoice->user->companySettings?->address,
                    'phone' => $invoice->user->companySettings?->phone,
                    'email' => $invoice->user->companySettings?->email,
                    'logo_url' => $invoice->user->companySettings?->logo
                        ? asset('storage/'.$invoice->user->companySettings->logo)
                        : null,
                    'bank_details' => $invoice->user->companySettings?->bank_details,
                ],
                'client' => [
                    'name' => $invoice->client->name,
                    'company' => $invoice->client->company,
                    'email' => $invoice->client->email,
                    'phone' => $invoice->client->phone,
                    'address' => $invoice->client->address,
                ],
                'items' => $invoice->items->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->total,
                ]),
            ],
        ]);
    }

    public function downloadPdf(string $token): Response
    {
        $invoice = Invoice::where('public_token', $token)->firstOrFail();

        return $this->pdfService->download($invoice);
    }
}

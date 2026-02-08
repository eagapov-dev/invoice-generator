<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Requests\Invoice\UpdateStatusRequest;
use App\Http\Resources\InvoiceResource;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\InvoiceNumberService;
use App\Services\InvoicePdfService;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceNumberService $invoiceNumberService,
        private InvoicePdfService $pdfService,
        private PlanLimitService $planLimitService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $invoices = $request->user()
            ->invoices()
            ->with(['client'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->client_id, fn ($q, $clientId) => $q->where('client_id', $clientId))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'ilike', "%{$search}%")
                        ->orWhereHas('client', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request): InvoiceResource
    {
        $invoice = DB::transaction(function () use ($request) {
            $currency = $request->currency
                ?? $request->user()->companySettings?->default_currency
                ?? 'USD';

            $template = $request->pdf_template ?? 'classic';
            if (! $this->planLimitService->canUseTemplate($request->user(), $template)) {
                $template = 'classic';
            }

            $invoice = $request->user()->invoices()->create([
                'invoice_number' => $this->invoiceNumberService->generate($request->user()),
                'client_id' => $request->client_id,
                'tax_percent' => $request->tax_percent ?? 0,
                'discount' => $request->discount ?? 0,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'status' => $request->status ?? InvoiceStatus::Draft,
                'currency' => $currency,
                'pdf_template' => $template,
            ]);

            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            $invoice->calculateTotals();

            return $invoice;
        });

        $request->user()->increment('monthly_invoice_count');

        return new InvoiceResource($invoice->load(['client', 'items']));
    }

    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice->load(['client', 'items.product']));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        DB::transaction(function () use ($request, $invoice) {
            $invoice->update($request->safe()->except('items'));

            if ($request->has('items')) {
                // Delete removed items
                $keepIds = collect($request->items)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $invoice->items()->whereNotIn('id', $keepIds)->delete();

                // Update or create items
                foreach ($request->items as $item) {
                    $invoice->items()->updateOrCreate(
                        ['id' => $item['id'] ?? null],
                        [
                            'product_id' => $item['product_id'] ?? null,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'total' => $item['quantity'] * $item['price'],
                        ]
                    );
                }

                $invoice->calculateTotals();
            }
        });

        return new InvoiceResource($invoice->fresh()->load(['client', 'items.product']));
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(UpdateStatusRequest $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('update', $invoice);

        $invoice->update(['status' => $request->status]);

        return new InvoiceResource($invoice->load(['client', 'items']));
    }

    public function generatePdf(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return $this->pdfService->stream($invoice);
    }

    public function getPdfUrl(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        $url = URL::temporarySignedRoute(
            'invoices.pdf.download',
            now()->addMinutes(5),
            ['invoice' => $invoice->id]
        );

        return response()->json(['url' => $url]);
    }

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        // No authorization needed - signature validates the request
        return $this->pdfService->download($invoice);
    }

    public function toggleShare(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->public_token) {
            $invoice->update(['public_token' => null]);

            return response()->json([
                'shared' => false,
                'public_url' => null,
            ]);
        }

        $invoice->update(['public_token' => Str::random(32)]);

        return response()->json([
            'shared' => true,
            'public_url' => url("/p/{$invoice->public_token}"),
        ]);
    }

    public function send(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);

        if (! $invoice->client->email) {
            return response()->json([
                'message' => 'Client does not have an email address.',
            ], 422);
        }

        $invoice->load(['client', 'items', 'user.companySettings']);

        Mail::to($invoice->client->email)->send(new InvoiceMail($invoice));

        // Update status to sent if it was draft
        if ($invoice->status === InvoiceStatus::Draft) {
            $invoice->update(['status' => InvoiceStatus::Sent]);
        }

        return response()->json([
            'message' => 'Invoice sent successfully.',
        ]);
    }
}

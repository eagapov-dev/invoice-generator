<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecurringInvoiceResource;
use App\Models\RecurringInvoice;
use App\Services\PlanLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecurringInvoiceController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $recurring = $request->user()
            ->recurringInvoices()
            ->with(['client'])
            ->latest()
            ->paginate($this->perPage($request));

        return RecurringInvoiceResource::collection($recurring);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->planLimitService->canUseRecurring($request->user())) {
            return response()->json([
                'message' => 'Recurring invoices are not available on your plan. Please upgrade.',
            ], 403);
        }

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'frequency' => ['required', Rule::in(['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'tax_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'pdf_template' => ['sometimes', 'string', Rule::in(['classic', 'modern', 'minimal'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        // Verify client belongs to user
        $clientBelongsToUser = $request->user()->clients()->where('id', $validated['client_id'])->exists();
        if (! $clientBelongsToUser) {
            return response()->json(['message' => 'Client not found.'], 422);
        }

        $recurring = DB::transaction(function () use ($request, $validated) {
            $currency = $validated['currency']
                ?? $request->user()->companySettings?->default_currency
                ?? 'USD';

            $template = $validated['pdf_template'] ?? 'classic';
            if (! $this->planLimitService->canUseTemplate($request->user(), $template)) {
                $template = 'classic';
            }

            $recurring = $request->user()->recurringInvoices()->create([
                'client_id' => $validated['client_id'],
                'frequency' => $validated['frequency'],
                'start_date' => $validated['start_date'],
                'next_generate_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'tax_percent' => $validated['tax_percent'] ?? 0,
                'discount' => $validated['discount'] ?? 0,
                'currency' => $currency,
                'pdf_template' => $template,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $recurring->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            return $recurring;
        });

        return (new RecurringInvoiceResource($recurring->fresh()->load(['client', 'items'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, RecurringInvoice $recurringInvoice): RecurringInvoiceResource
    {
        $this->authorize('view', $recurringInvoice);

        return new RecurringInvoiceResource($recurringInvoice->load(['client', 'items']));
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice): RecurringInvoiceResource
    {
        $this->authorize('update', $recurringInvoice);

        $validated = $request->validate([
            'client_id' => ['sometimes', 'exists:clients,id'],
            'frequency' => ['sometimes', Rule::in(['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'])],
            'end_date' => ['nullable', 'date'],
            'tax_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'pdf_template' => ['sometimes', 'string', Rule::in(['classic', 'modern', 'minimal'])],
            'notes' => ['nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $recurringInvoice) {
            $recurringInvoice->update(collect($validated)->except('items')->toArray());

            if (isset($validated['items'])) {
                $keepIds = collect($validated['items'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $recurringInvoice->items()->whereNotIn('id', $keepIds)->delete();

                foreach ($validated['items'] as $item) {
                    $recurringInvoice->items()->updateOrCreate(
                        ['id' => $item['id'] ?? null],
                        [
                            'product_id' => $item['product_id'] ?? null,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                        ]
                    );
                }
            }
        });

        return new RecurringInvoiceResource($recurringInvoice->fresh()->load(['client', 'items']));
    }

    public function destroy(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorize('delete', $recurringInvoice);

        $recurringInvoice->delete();

        return response()->json(null, 204);
    }

    public function toggleActive(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->update(['is_active' => ! $recurringInvoice->is_active]);

        return response()->json([
            'is_active' => $recurringInvoice->is_active,
        ]);
    }
}

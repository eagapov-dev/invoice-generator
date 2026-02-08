<?php

namespace App\Http\Controllers\Api;

use App\Exports\InvoicesExport;
use App\Http\Controllers\Controller;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService
    ) {}

    public function csv(Request $request): StreamedResponse
    {
        if (! $this->planLimitService->canExport($request->user())) {
            abort(403, 'Your plan does not include export functionality. Please upgrade.');
        }

        $invoices = $this->getFilteredInvoices($request);

        $filename = 'invoices-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($invoices) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
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
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
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
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function excel(Request $request): BinaryFileResponse
    {
        if (! $this->planLimitService->canExport($request->user())) {
            abort(403, 'Your plan does not include export functionality. Please upgrade.');
        }

        $invoices = $this->getFilteredInvoices($request);

        $filename = 'invoices-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new InvoicesExport($invoices), $filename);
    }

    private function getFilteredInvoices(Request $request)
    {
        return $request->user()
            ->invoices()
            ->with(['client'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->client_id, fn ($q, $clientId) => $q->where('client_id', $clientId))
            ->when($request->search, function ($query, $search) {
                $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $query->where(function ($q) use ($search, $like) {
                    $q->where('invoice_number', $like, "%{$search}%")
                        ->orWhereHas('client', fn ($q) => $q->where('name', $like, "%{$search}%"));
                });
            })
            ->latest()
            ->get();
    }
}

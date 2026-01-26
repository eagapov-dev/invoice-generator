<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalInvoices = $user->invoices()->count();

        $paidTotal = $user->invoices()
            ->where('status', InvoiceStatus::Paid)
            ->sum('total');

        $unpaidTotal = $user->invoices()
            ->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->sum('total');

        $overdueTotal = $user->invoices()
            ->where('status', InvoiceStatus::Overdue)
            ->sum('total');

        $statusCounts = $user->invoices()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $recentInvoices = $user->invoices()
            ->with('client')
            ->latest()
            ->limit(5)
            ->get();

        $totalClients = $user->clients()->count();
        $totalProducts = $user->products()->count();

        return response()->json([
            'stats' => [
                'total_invoices' => $totalInvoices,
                'paid_total' => (float) $paidTotal,
                'unpaid_total' => (float) $unpaidTotal,
                'overdue_total' => (float) $overdueTotal,
                'total_clients' => $totalClients,
                'total_products' => $totalProducts,
                'status_counts' => [
                    'draft' => $statusCounts['draft'] ?? 0,
                    'sent' => $statusCounts['sent'] ?? 0,
                    'paid' => $statusCounts['paid'] ?? 0,
                    'overdue' => $statusCounts['overdue'] ?? 0,
                ],
            ],
            'recent_invoices' => InvoiceResource::collection($recentInvoices),
        ]);
    }
}

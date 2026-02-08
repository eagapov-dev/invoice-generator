<?php

namespace App\Http\Middleware;

use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    public function __construct(private PlanLimitService $planLimitService) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $canCreate = match ($resource) {
            'invoice' => $this->planLimitService->canCreateInvoice($user),
            'client' => $this->planLimitService->canCreateClient($user),
            'product' => $this->planLimitService->canCreateProduct($user),
            default => true,
        };

        if (! $canCreate) {
            $plan = $user->activePlan();
            $limits = $this->getLimitInfo($user, $plan, $resource);

            return response()->json([
                'message' => 'You have reached the limit for your current plan.',
                'error' => 'plan_limit_exceeded',
                'resource' => $resource . 's',
                'current' => $limits['current'],
                'limit' => $limits['limit'],
                'upgrade_url' => '/pricing',
            ], 403);
        }

        return $next($request);
    }

    private function getLimitInfo($user, $plan, string $resource): array
    {
        return match ($resource) {
            'invoice' => [
                'current' => $user->monthly_invoice_count,
                'limit' => $plan->max_invoices_per_month,
            ],
            'client' => [
                'current' => $user->clients()->count(),
                'limit' => $plan->max_clients,
            ],
            'product' => [
                'current' => $user->products()->count(),
                'limit' => $plan->max_products,
            ],
            default => ['current' => 0, 'limit' => 0],
        };
    }
}

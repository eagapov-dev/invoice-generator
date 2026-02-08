<?php

namespace App\Http\Resources;

use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plan = $this->activePlan();
        $planLimitService = app(PlanLimitService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'plan' => [
                'name' => $plan->name,
                'slug' => $plan->slug,
            ],
            'limits' => $planLimitService->getLimitsOverview($this->resource),
        ];
    }
}

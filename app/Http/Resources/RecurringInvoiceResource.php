<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'frequency' => $this->frequency,
            'start_date' => $this->start_date->format('Y-m-d'),
            'next_generate_date' => $this->next_generate_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'tax_percent' => (float) $this->tax_percent,
            'discount' => (float) $this->discount,
            'currency' => $this->currency ?? 'USD',
            'pdf_template' => $this->pdf_template ?? 'classic',
            'notes' => $this->notes,
            'total_generated' => $this->total_generated,
            'last_generated_at' => $this->last_generated_at?->toISOString(),
            'items' => RecurringInvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

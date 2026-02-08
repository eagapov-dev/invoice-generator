<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'client_id' => $this->client_id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'subtotal' => (float) $this->subtotal,
            'tax_percent' => (float) $this->tax_percent,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'currency' => $this->currency ?? 'USD',
            'pdf_template' => $this->pdf_template ?? 'classic',
            'public_token' => $this->public_token,
            'public_url' => $this->public_token ? url("/p/{$this->public_token}") : null,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

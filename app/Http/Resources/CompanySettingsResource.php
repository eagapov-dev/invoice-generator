<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? asset('storage/'.$this->logo) : null,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'bank_details' => $this->bank_details,
            'default_currency' => $this->default_currency,
            'default_tax_percent' => (float) $this->default_tax_percent,
        ];
    }
}

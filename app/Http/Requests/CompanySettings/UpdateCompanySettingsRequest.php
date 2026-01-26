<?php

namespace App\Http\Requests\CompanySettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'bank_details' => ['nullable', 'string', 'max:2000'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

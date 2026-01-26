<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettings\UpdateCompanySettingsRequest;
use App\Http\Resources\CompanySettingsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $request->user()->companySettings;

        if (! $settings) {
            $settings = $request->user()->companySettings()->create([
                'default_currency' => 'USD',
                'default_tax_percent' => 0,
            ]);
        }

        return response()->json([
            'data' => new CompanySettingsResource($settings),
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): JsonResponse
    {
        $settings = $request->user()->companySettings;

        if (! $settings) {
            $settings = $request->user()->companySettings()->create($request->validated());
        } else {
            $settings->update($request->validated());
        }

        return response()->json([
            'data' => new CompanySettingsResource($settings),
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'], // 2MB max
        ]);

        $settings = $request->user()->companySettings;

        if (! $settings) {
            $settings = $request->user()->companySettings()->create([
                'default_currency' => 'USD',
                'default_tax_percent' => 0,
            ]);
        }

        // Delete old logo if exists
        if ($settings->logo) {
            Storage::disk('public')->delete($settings->logo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $settings->update(['logo' => $path]);

        return response()->json([
            'data' => new CompanySettingsResource($settings),
            'message' => 'Logo uploaded successfully.',
        ]);
    }
}

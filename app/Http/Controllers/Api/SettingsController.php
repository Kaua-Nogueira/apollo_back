<?php

namespace App\Http\Controllers\Api;

use App\Models\CompanySetting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SettingsController
{
    public function show()
    {
        return response()->json(['data' => CompanySetting::firstOrCreate([], ['company_name' => 'Minha empresa'])]);
    }

    public function update(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:120', 'legal_name' => 'nullable|string|max:160',
            'document' => 'nullable|string|max:30', 'state_registration' => 'nullable|string|max:30',
            'municipal_registration' => 'nullable|string|max:30', 'contact_name' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30', 'whatsapp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120', 'website' => 'nullable|string|max:180',
            'zip_code' => 'nullable|string|max:15', 'street' => 'nullable|string|max:180',
            'number' => 'nullable|string|max:30', 'complement' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:100', 'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|size:2', 'pix_key' => 'nullable|string|max:160',
            'slogan' => 'nullable|string|max:180', 'document_accent_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'document_footer_text' => 'nullable|string|max:500', 'quote_terms' => 'nullable|string|max:2000',
            'receipt_terms' => 'nullable|string|max:2000', 'timezone' => 'required|string|max:60',
            'logo_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'document_header_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:6144',
            'document_footer_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:6144',
        ]);

        foreach (['logo_image', 'document_header_image', 'document_footer_image'] as $field) {
            if ($request->hasFile($field)) $data[$field] = $request->file($field)->store('company-branding', 'public');
            else unset($data[$field]);
        }

        $settings = CompanySetting::firstOrCreate([], ['company_name' => 'Minha empresa']);
        $before = $settings->only(array_keys($data));
        $settings->update($data);
        $audit->record('company_settings_updated', $settings, $before, $data);
        return response()->json(['data' => $settings->fresh()]);
    }
}

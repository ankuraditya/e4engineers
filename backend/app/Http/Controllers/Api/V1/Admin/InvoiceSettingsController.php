<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSetting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceSettingsController extends Controller
{
    use ApiResponse;

    public function show(): JsonResponse
    {
        return $this->successResponse(InvoiceSetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => 'sometimes|required|string|max:150', 'display_name' => 'sometimes|required|string|max:150',
            'gstin' => ['nullable', 'string', 'max:20'], 'pan' => ['nullable', 'string', 'max:20'],
            'address_line_1' => 'nullable|string|max:255', 'address_line_2' => 'nullable|string|max:255', 'city' => 'nullable|string|max:100', 'state' => 'nullable|string|max:100', 'postal_code' => 'nullable|string|max:12', 'country' => 'nullable|string|max:100', 'phone' => 'nullable|string|max:30', 'email' => 'nullable|email|max:255',
            'invoice_prefix' => 'sometimes|required|string|max:30', 'number_format' => ['sometimes', 'required', 'string', 'max:100', 'regex:/\{SEQUENCE\}/'],
            'period_strategy' => ['sometimes', Rule::in(['financial_year', 'calendar_year', 'none'])], 'sequence_reset' => ['sometimes', Rule::in(['financial_year', 'yearly', 'never'])], 'sequence_padding' => 'sometimes|integer|between:3,12',
            'footer_text' => 'nullable|string|max:2000', 'terms' => 'nullable|string|max:5000', 'declaration' => 'nullable|string|max:5000', 'authorized_signatory_name' => 'nullable|string|max:150', 'authorized_signatory_designation' => 'nullable|string|max:150',
            'show_gst_columns' => 'sometimes|boolean', 'show_hsn' => 'sometimes|boolean', 'show_discount' => 'sometimes|boolean', 'show_shipping' => 'sometimes|boolean', 'show_payment_method' => 'sometimes|boolean', 'show_payment_reference' => 'sometimes|boolean',
            'logo' => 'sometimes|file|mimes:png,jpg,jpeg|max:2048', 'signature' => 'sometimes|file|mimes:png,jpg,jpeg|max:1024',
        ]);
        $settings = InvoiceSetting::current();
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('invoice-assets', 'local');
        }
        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('invoice-assets', 'local');
        }
        unset($data['logo'], $data['signature']);
        $settings->update($data);

        return $this->successResponse($settings->refresh(), 'Invoice settings updated.');
    }
}

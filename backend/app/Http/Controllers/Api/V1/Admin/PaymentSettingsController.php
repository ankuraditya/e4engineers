<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProvider;
use App\Models\PaymentSetting;
use App\Services\Payment\PaymentGatewayManager;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PaymentSettingsController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentGatewayManager $gateways) {}

    public function settings(): JsonResponse
    {
        return $this->successResponse(PaymentSetting::current());
    }

    public function updateSettings(Request $r): JsonResponse
    {
        $s = PaymentSetting::current();
        $data = $r->validate(['online_payments_enabled' => 'sometimes|boolean', 'cod_enabled' => 'sometimes|boolean', 'cod_charge' => 'sometimes|numeric|min:0', 'attempt_expiry_minutes' => 'sometimes|integer|between:5,1440']);
        $s->update($data);
        if (array_key_exists('cod_enabled', $data)) {
            PaymentProvider::where('code', 'COD')->update(['is_enabled' => $data['cod_enabled']]);
        }

        return $this->successResponse($s->refresh(), 'Payment settings updated.');
    }

    public function providers(): JsonResponse
    {
        return $this->successResponse(PaymentProvider::orderBy('sort_order')->get()->map(fn ($p) => $this->providerData($p)));
    }

    public function show(PaymentProvider $provider): JsonResponse
    {
        return $this->successResponse($this->providerData($provider));
    }

    public function credentials(Request $r, PaymentProvider $provider): JsonResponse
    {
        $adapter = $this->gateways->for($provider);
        $allowed = collect($adapter->credentialFields())->pluck('name')->all();
        $data = $r->validate(['credentials' => 'required|array', 'credentials.*' => 'nullable|string|max:1000', 'clear' => 'sometimes|array', 'clear.*' => Rule::in($allowed)]);
        $config = $provider->configuration ?? [];
        foreach ($allowed as $field) {
            if (in_array($field, $data['clear'] ?? [], true)) {
                unset($config[$field]);
            } elseif (filled($data['credentials'][$field] ?? null)) {
                $config[$field] = $data['credentials'][$field];
            }
        }$required = collect($adapter->credentialFields())->pluck('name');
        $configured = $required->every(fn ($key) => filled($config[$key] ?? null));
        $provider->update(['configuration' => $config, 'connection_status' => $configured ? 'untested' : 'not_configured', 'is_enabled' => $configured ? $provider->is_enabled : false]);
        $this->audit($r, $provider, 'credentials_updated');

        return $this->successResponse($this->providerData($provider->refresh()), 'Credentials updated.');
    }

    public function scanCode(Request $request, PaymentProvider $provider): JsonResponse
    {
        abort_unless($provider->code === 'SCANPAY', 404);
        $data = $request->validate([
            'upi_id' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/'],
            'payee_name' => ['required', 'string', 'max:120'],
            'qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);
        $configuration = $provider->configuration ?? [];
        if (! $request->hasFile('qr') && empty($configuration['qr_path'])) {
            abort(422, 'Upload a payment QR image before saving this method.');
        }
        if (! $request->hasFile('qr') && filled($configuration['qr_path'] ?? null) && ($configuration['upi_id'] ?? null) !== $data['upi_id']) {
            abort(422, 'Upload a new QR image when changing the UPI ID.');
        }
        $oldPath = $configuration['qr_path'] ?? null;
        if ($request->hasFile('qr')) {
            $configuration['qr_path'] = $request->file('qr')->store('payments/qr', 'private');
            abort_unless($configuration['qr_path'], 500, 'QR upload failed.');
        }
        $configuration['upi_id'] = $data['upi_id'];
        $configuration['payee_name'] = $data['payee_name'];
        $provider->update(['configuration' => $configuration, 'connection_status' => 'connected']);
        if ($oldPath && $oldPath !== $configuration['qr_path']) {
            Storage::disk('private')->delete($oldPath);
        }
        $this->audit($request, $provider, 'scan_code_updated');

        return $this->successResponse($this->providerData($provider->refresh()), 'Scan & Pay settings saved.');
    }

    public function environment(Request $r, PaymentProvider $provider): JsonResponse
    {
        $allowed = $provider->code === 'CASHFREE' ? ['sandbox', 'live'] : ['test', 'live'];
        $d = $r->validate(['environment' => ['required', Rule::in($allowed)]]);
        $provider->update(['environment' => $d['environment'], 'configuration' => null, 'connection_status' => 'not_configured', 'is_enabled' => false, 'is_default' => false]);
        $this->audit($r, $provider, 'environment_changed');

        return $this->successResponse($this->providerData($provider->refresh()));
    }

    public function test(Request $r, PaymentProvider $provider): JsonResponse
    {
        $result = $this->gateways->for($provider)->testConnection($provider);
        $provider->update(['connection_status' => $result['connected'] ? 'connected' : 'failed', 'last_connection_test_at' => now(), 'last_connection_error' => $result['connected'] ? null : 'Connection failed']);
        $this->audit($r, $provider, 'connection_tested');

        return $this->successResponse(['connected' => $result['connected']]);
    }

    public function toggle(Request $r, PaymentProvider $provider): JsonResponse
    {
        $enabled = $r->validate(['enabled' => 'required|boolean'])['enabled'];
        if ($enabled && $provider->code === 'SCANPAY') {
            $configuration = $provider->configuration ?? [];
            abort_unless(filled($configuration['upi_id'] ?? null) && filled($configuration['payee_name'] ?? null) && filled($configuration['qr_path'] ?? null) && Storage::disk('private')->exists($configuration['qr_path']), 422, 'Save a UPI ID, payee name, and QR image before enabling Scan & Pay.');
        }
        if ($enabled && $provider->type === 'online' && $provider->connection_status !== 'connected') {
            abort(422, 'Test the connection successfully before enabling.');
        }
        $provider->update(['is_enabled' => $enabled, 'is_default' => $enabled ? $provider->is_default : false]);
        if ($provider->code === 'COD') {
            PaymentSetting::current()->update(['cod_enabled' => $enabled]);
        }

        return $this->successResponse($this->providerData($provider->refresh()));
    }

    public function default(PaymentProvider $provider): JsonResponse
    {
        abort_unless($provider->type === 'online' && $provider->is_enabled && $provider->connection_status === 'connected', 422);
        DB::transaction(function () use ($provider) {
            PaymentProvider::where('type', 'online')->update(['is_default' => false]);
            $provider->update(['is_default' => true]);
        });

        return $this->successResponse($this->providerData($provider->refresh()));
    }

    private function providerData(PaymentProvider $p): array
    {
        $configuration = $p->configuration ?? [];

        return ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'type' => $p->type, 'is_enabled' => $p->is_enabled, 'is_default' => $p->is_default, 'environment' => $p->environment, 'connection_status' => $p->connection_status, 'last_connection_test_at' => $p->last_connection_test_at, 'configuration' => $p->type === 'online' ? $p->publicConfiguration() : ($p->code === 'SCANPAY' ? ['upi_id' => $configuration['upi_id'] ?? '', 'payee_name' => $configuration['payee_name'] ?? '', 'qr_configured' => filled($configuration['qr_path'] ?? null)] : []), 'credential_fields' => $p->type === 'online' ? $this->gateways->for($p)->credentialFields() : []];
    }

    private function audit(Request $r, PaymentProvider $p, string $event): void
    {
        DB::table('payment_audit_logs')->insert(['user_id' => $r->user()->id, 'payment_provider_id' => $p->id, 'event' => $event, 'context' => json_encode(['environment' => $p->environment]), 'created_at' => now(), 'updated_at' => now()]);
    }
}

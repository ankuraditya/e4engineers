<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $invoice = $this->ownedInvoice($request, $orderNumber);

        return $this->successResponse($invoice->load('items'));
    }

    public function download(Request $request, string $orderNumber, InvoiceService $service): StreamedResponse
    {
        $invoice = $this->ownedInvoice($request, $orderNumber);
        if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            $invoice = $service->regeneratePdfFromImmutableInvoice($invoice);
        }

        return Storage::disk('local')->download($invoice->pdf_path, str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function ownedInvoice(Request $request, string $orderNumber): Invoice
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $token = (string) $request->header('X-Order-Access-Token');
        abort_unless(($request->user() && $order->user_id === $request->user()->id) || ($token !== '' && hash_equals((string) $order->guest_access_token_hash, hash('sha256', $token))), 404);

        return Invoice::where('order_id', $order->id)->where('status', 'issued')->firstOrFail();
    }
}

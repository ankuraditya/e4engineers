<?php

namespace App\Http\Controllers\Api\V1\Admin;

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

    public function index(Request $request): JsonResponse
    {
        $items = Invoice::with('order:id,order_number')->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->latest('issued_at')->paginate(25);

        return $this->successResponse($items->items(), meta: ['pagination' => ['total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return $this->successResponse($invoice->load(['items', 'order']));
    }

    public function issue(Order $order, InvoiceService $service): JsonResponse
    {
        return $this->successResponse($service->issueForOrder($order), status: 201);
    }

    public function download(Invoice $invoice, InvoiceService $service): StreamedResponse
    {
        if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            $invoice = $service->regeneratePdfFromImmutableInvoice($invoice);
        }

        return Storage::disk('local')->download($invoice->pdf_path, str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function regenerate(Invoice $invoice, InvoiceService $service): JsonResponse
    {
        return $this->successResponse($service->regeneratePdfFromImmutableInvoice($invoice), 'Invoice PDF regenerated from its immutable snapshot.');
    }

    public function void(Request $request, Invoice $invoice, InvoiceService $service): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:1000']);

        return $this->successResponse($service->voidInvoice($invoice, $data['reason'], $request->user()->id), 'Invoice voided.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessDocument;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\BusinessDocumentService;
use Illuminate\Http\Request;

class BusinessDocumentController extends Controller
{
    public function index(Request $request)
    {
        return BusinessDocument::with(['customer', 'workOrder', 'creator', 'items'])
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->work_order_id, fn ($query, $woId) => $query->where('work_order_id', $woId))
            ->latest('issued_at')
            ->latest('id')
            ->paginate(100);
    }

    public function show(BusinessDocument $document)
    {
        return response()->json(['data' => $document->load(['customer', 'workOrder', 'creator', 'items'])]);
    }

    public function store(Request $request, BusinessDocumentService $documents, AuditService $audit)
    {
        $data = $request->validate([
            'type' => 'required|in:quote,receipt',
            'customer_id' => 'nullable|exists:customers,id',
            'address_id' => 'nullable|exists:customer_addresses,id',
            'work_order_id' => 'nullable|exists:work_orders,id',
            'customer_name' => 'required_without:customer_id|nullable|string|max:160',
            'customer_document' => 'nullable|string|max:30',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|email|max:160',
            'customer_address' => 'nullable|string|max:500',
            'issued_at' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issued_at',
            'payment_method' => 'nullable|in:pix,cash,credit_card,debit_card,transfer,bank_slip,other',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:30',
            'items.*.name' => 'required|string|max:180',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $document = $documents->create($data, $request->user()->id);
        $audit->record('business_document_created', $document, [], $document->toArray());

        return response()->json(['data' => $document], 201);
    }

    public function generateWorkOrder(Request $request, BusinessDocument $document, BusinessDocumentService $documents, AuditService $audit)
    {
        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'technician_id' => 'nullable|exists:employees,id',
            'problem_reported' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order = $documents->generateWorkOrderFromQuote($document, $data, $request->user()->id);
        $audit->record('work_order_generated_from_quote', $order, [], [
            'document_id' => $document->id,
            'document_number' => $document->number,
            'work_order_number' => $order->number,
        ]);

        return response()->json([
            'message' => "Ordem de Serviço {$order->number} gerada com sucesso a partir do orçamento!",
            'data' => $order,
            'document' => $document->fresh()->load(['customer', 'workOrder', 'items']),
        ], 201);
    }

    public function generateReceiptFromWorkOrder(Request $request, WorkOrder $workOrder, BusinessDocumentService $documents, AuditService $audit)
    {
        $data = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'payment_method' => 'nullable|in:pix,cash,credit_card,debit_card,transfer,bank_slip,other',
            'issued_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'register_payment' => 'nullable|boolean',
            'items' => 'nullable|array|min:1',
            'items.*.name' => 'required_with:items|string|max:180',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ]);

        $receipt = $documents->generateReceiptFromWorkOrder($workOrder, $data, $request->user()->id);
        $audit->record('receipt_generated_from_work_order', $receipt, [], [
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->number,
            'receipt_number' => $receipt->number,
        ]);

        return response()->json([
            'message' => "Recibo {$receipt->number} gerado com sucesso!",
            'data' => $receipt,
        ], 201);
    }
}

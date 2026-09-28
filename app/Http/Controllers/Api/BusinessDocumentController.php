<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessDocument;
use App\Services\AuditService;
use App\Services\BusinessDocumentService;
use Illuminate\Http\Request;

class BusinessDocumentController extends Controller
{
    public function index(Request $request)
    {
        return BusinessDocument::with(['customer', 'workOrder', 'creator', 'items'])
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
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
}

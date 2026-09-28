<?php

namespace App\Services;

use App\Models\BusinessDocument;
use App\Models\BusinessDocumentItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;

class BusinessDocumentService
{
    public function create(array $data, ?int $userId): BusinessDocument
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = isset($data['customer_id']) ? Customer::find($data['customer_id']) : null;
            $address = isset($data['address_id']) ? CustomerAddress::find($data['address_id']) : null;
            $items = $data['items'];
            $subtotal = collect($items)->sum(fn (array $item) => round((float) $item['quantity'] * (float) $item['unit_price'], 2));
            $discount = (float) ($data['discount'] ?? 0);
            $prefix = $data['type'] === 'quote' ? 'ORC' : 'REC';
            $sequence = (BusinessDocument::lockForUpdate()->max('id') ?? 0) + 1;

            $document = BusinessDocument::create([
                'type' => $data['type'],
                'number' => $prefix.'-'.now()->format('Ym').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer?->id,
                'work_order_id' => $data['work_order_id'] ?? null,
                'created_by' => $userId,
                'customer_name' => $customer?->name ?? $data['customer_name'],
                'customer_document' => $customer?->document ?? ($data['customer_document'] ?? null),
                'customer_phone' => $customer?->phone ?? ($data['customer_phone'] ?? null),
                'customer_email' => $customer?->email ?? ($data['customer_email'] ?? null),
                'customer_address' => $address ? $this->formatAddress($address) : ($data['customer_address'] ?? null),
                'issued_at' => $data['issued_at'],
                'valid_until' => $data['type'] === 'quote' ? ($data['valid_until'] ?? null) : null,
                'payment_method' => $data['type'] === 'receipt' ? ($data['payment_method'] ?? null) : null,
                'status' => 'issued',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => max(0, $subtotal - $discount),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                BusinessDocumentItem::create([
                    'business_document_id' => $document->id,
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                ]);
            }

            return $document->load(['customer', 'workOrder', 'creator', 'items']);
        });
    }

    private function formatAddress(CustomerAddress $address): string
    {
        return collect([
            trim($address->street.', '.$address->number),
            $address->complement,
            $address->district,
            trim($address->city.'/'.$address->state, '/'),
            $address->zip_code ? 'CEP '.$address->zip_code : null,
        ])->filter()->join(' · ');
    }
}

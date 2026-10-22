<?php

namespace App\Services;

use App\Models\BusinessDocument;
use App\Models\BusinessDocumentItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Models\WorkOrderStatusHistory;
use App\Services\ReceivableService;
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

    public function generateWorkOrderFromQuote(BusinessDocument $document, array $data, ?int $userId): WorkOrder
    {
        if ($document->type !== 'quote') {
            abort(422, 'Apenas orçamentos podem gerar ordens de serviço.');
        }

        if ($document->work_order_id && WorkOrder::where('id', $document->work_order_id)->exists()) {
            $existing = WorkOrder::find($document->work_order_id);
            abort(422, "Este orçamento já gerou a Ordem de Serviço {$existing?->number}.");
        }

        return DB::transaction(function () use ($document, $data, $userId) {
            // 1. Resolve Customer
            $customer = null;
            if ($document->customer_id) {
                $customer = Customer::find($document->customer_id);
            }
            if (!$customer) {
                $customer = Customer::where('name', $document->customer_name)
                    ->when($document->customer_document, fn ($q, $doc) => $q->orWhere('document', $doc))
                    ->first();
            }
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $document->customer_name,
                    'document' => $document->customer_document,
                    'phone' => $document->customer_phone ?: '11999999999',
                    'email' => $document->customer_email,
                    'active' => true,
                ]);
            }

            // 2. Resolve Address
            $address = null;
            if ($document->customer_id) {
                $address = $customer->addresses()->first();
            }
            if (!$address) {
                $address = CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => 'Principal',
                    'street' => $document->customer_address ?: 'Endereço principal',
                    'number' => 'S/N',
                    'district' => 'Centro',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                ]);
            }

            // 3. Next OS number
            $next = (WorkOrder::withTrashed()->max('id') ?? 0) + 1;
            $number = 'OS-'.now()->format('Ym').'-'.str_pad((string)$next, 4, '0', STR_PAD_LEFT);

            // 4. Create Work Order
            $order = WorkOrder::create([
                'number' => $number,
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'technician_id' => !empty($data['technician_id']) ? $data['technician_id'] : null,
                'scheduled_at' => !empty($data['scheduled_at']) ? $data['scheduled_at'] : now(),
                'problem_reported' => !empty($data['problem_reported'])
                    ? $data['problem_reported']
                    : "Orçamento {$document->number} aprovado pelo cliente",
                'notes' => !empty($data['notes']) ? $data['notes'] : $document->notes,
                'status' => 'scheduled',
                'subtotal' => 0,
                'discount' => (float) ($document->discount ?? 0),
                'total' => 0,
            ]);

            // 5. Work Order Status History
            WorkOrderStatusHistory::create([
                'work_order_id' => $order->id,
                'user_id' => $userId,
                'to_status' => 'scheduled',
                'occurred_at' => now(),
                'notes' => "OS gerada a partir da confirmação do Orçamento {$document->number}",
            ]);

            // 6. Copy Items as WorkOrderServices
            foreach ($document->items as $item) {
                $service = Service::whereRaw('LOWER(name) = ?', [strtolower(trim($item->name))])->first();
                if (!$service) {
                    $service = Service::create([
                        'name' => $item->name,
                        'category' => 'Orçamento',
                        'default_price' => $item->unit_price,
                        'active' => true,
                    ]);
                }

                $quantity = (float) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                WorkOrderService::create([
                    'work_order_id' => $order->id,
                    'service_id' => $service->id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'total' => round($quantity * $unitPrice, 2),
                ]);
            }

            // 7. Calculate and sync
            $subtotal = (float) $order->services()->sum('total');
            $discount = (float) $order->discount;
            $order->update([
                'subtotal' => $subtotal,
                'total' => max(0, $subtotal - $discount),
            ]);

            app(ReceivableService::class)->sync($order);

            // 8. Link to document & mark approved
            $document->update([
                'work_order_id' => $order->id,
                'status' => 'approved',
            ]);

            return $order->load(['customer', 'address', 'equipment', 'technician', 'services', 'products', 'payments', 'statusHistory']);
        });
    }

    public function generateReceiptFromWorkOrder(WorkOrder $workOrder, array $data, ?int $userId): BusinessDocument
    {
        return DB::transaction(function () use ($workOrder, $data, $userId) {
            $workOrder->loadMissing(['customer', 'address', 'services', 'products', 'payments']);

            $amount = isset($data['amount']) ? (float) $data['amount'] : (float) ($workOrder->received > 0 ? $workOrder->received : $workOrder->total);
            if ($amount <= 0) {
                $amount = (float) $workOrder->total;
            }

            $paymentMethod = $data['payment_method'] ?? ($workOrder->payments->last()?->method ?? 'pix');
            $issuedAt = $data['issued_at'] ?? now()->toDateString();
            $notes = $data['notes'] ?? "Recibo de pagamento referente à Ordem de Serviço {$workOrder->number}.";

            // If register_payment is true (or default when omitted) and order has balance > 0
            $shouldRegister = array_key_exists('register_payment', $data) ? (bool) $data['register_payment'] : true;
            if ($shouldRegister && $workOrder->balance > 0) {
                $payAmount = min($amount, (float) $workOrder->balance);
                $workOrder->payments()->create([
                    'amount' => $payAmount,
                    'method' => $paymentMethod,
                    'paid_at' => now(),
                    'notes' => 'Registrado automaticamente na emissão do recibo',
                    'created_by' => $userId,
                ]);
                app(ReceivableService::class)->sync($workOrder->fresh());
            }

            // 1. Always gather all services and products directly from the Work Order
            $items = [];
            if (!empty($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } else {
                foreach ($workOrder->services as $svc) {
                    $items[] = [
                        'name' => $svc->name,
                        'quantity' => (float) $svc->quantity,
                        'unit_price' => (float) $svc->unit_price,
                    ];
                }
                foreach ($workOrder->products as $prd) {
                    $items[] = [
                        'name' => $prd->name,
                        'quantity' => (float) $prd->quantity,
                        'unit_price' => (float) $prd->unit_price,
                    ];
                }

                // Fallback only if the OS has neither services nor products
                if (empty($items)) {
                    $items[] = [
                        'name' => !empty($workOrder->problem_reported)
                            ? "Atendimento técnico: {$workOrder->problem_reported}"
                            : "Serviços prestados ref. à Ordem de Serviço {$workOrder->number}",
                        'quantity' => 1,
                        'unit_price' => $amount > 0 ? $amount : (float) $workOrder->total,
                    ];
                }
            }

            $discount = (float) ($workOrder->discount ?? 0);

            $documentData = [
                'type' => 'receipt',
                'work_order_id' => $workOrder->id,
                'customer_id' => $workOrder->customer_id,
                'address_id' => $workOrder->address_id,
                'customer_name' => $workOrder->customer->name,
                'customer_document' => $workOrder->customer->document,
                'customer_phone' => $workOrder->customer->phone,
                'customer_email' => $workOrder->customer->email,
                'customer_address' => $workOrder->address ? $this->formatAddress($workOrder->address) : null,
                'issued_at' => $issuedAt,
                'payment_method' => $paymentMethod,
                'discount' => $discount,
                'notes' => $notes,
                'items' => $items,
            ];

            return $this->create($documentData, $userId);
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

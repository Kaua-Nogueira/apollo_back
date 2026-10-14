<?php
namespace App\Http\Controllers\Api;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\ReceivableService;
use App\Services\WorkOrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController
{
    public function store(Request $request, WorkOrder $workOrder, AuditService $audit, ReceivableService $receivables, WorkOrderAccessService $access)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:.01',
            'method' => 'required|in:pix,cash,credit_card,debit_card,transfer,bank_slip,other',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $workOrder, $audit, $receivables, $access, $data) {
            $lockedOrder = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            $access->ensureCanOperate($request->user(), $lockedOrder);

            if ((float) $data['amount'] > $lockedOrder->balance) {
                return response()->json(['message' => 'O pagamento não pode ser maior que o saldo da OS.'], 422);
            }

            $payment = Payment::create([
                ...$data,
                'work_order_id' => $lockedOrder->id,
                'paid_at' => $data['paid_at'] ?? now(),
                'created_by' => $request->user()->id,
            ]);
            $receivables->sync($lockedOrder->fresh());
            $audit->record('payment_created', $payment, [], $payment->toArray());

            return response()->json(['data' => $payment], 201);
        });
    }
}

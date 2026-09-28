<?php
namespace App\Http\Controllers\Api;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Services\AuditService;
use App\Services\ReceivableService;
use App\Services\WorkOrderAccessService;
use Illuminate\Http\Request;
class PaymentController { public function store(Request $request,WorkOrder $workOrder,AuditService $audit,ReceivableService $receivables,WorkOrderAccessService $access){$access->ensureCanOperate($request->user(),$workOrder);$data=$request->validate(['amount'=>'required|numeric|min:.01','method'=>'required|in:pix,cash,credit_card,debit_card,transfer,bank_slip,other','paid_at'=>'nullable|date','notes'=>'nullable|string']);if((float)$data['amount']>$workOrder->balance)return response()->json(['message'=>'O pagamento não pode ser maior que o saldo da OS.'],422);$payment=Payment::create([...$data,'work_order_id'=>$workOrder->id,'paid_at'=>$data['paid_at']??now(),'created_by'=>$request->user()->id]);$receivables->sync($workOrder->fresh());$audit->record('payment_created',$payment,[],$payment->toArray());return response()->json(['data'=>$payment],201);} }

<?php
namespace App\Services;
use App\Models\Receivable;
use App\Models\WorkOrder;
class ReceivableService { public function sync(WorkOrder $order): Receivable { $order->loadMissing('payments','customer');$received=$order->received;$balance=max(0,(float)$order->total-$received);$status=$balance<=0?'paid':($received>0?'partial':(optional($order->scheduled_at)->isPast()?'overdue':'open'));return Receivable::updateOrCreate(['work_order_id'=>$order->id],['customer_id'=>$order->customer_id,'due_date'=>optional($order->scheduled_at)->toDateString(),'amount'=>$order->total,'received'=>$received,'status'=>$status]); } }

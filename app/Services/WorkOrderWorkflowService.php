<?php
namespace App\Services;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class WorkOrderWorkflowService {
    private const TRANSITIONS=['start_travel'=>['from'=>['scheduled','confirmed'],'to'=>'traveling'],'arrive'=>['from'=>['traveling'],'to'=>'arrived'],'start_service'=>['from'=>['arrived'],'to'=>'in_service'],'await_approval'=>['from'=>['in_service'],'to'=>'awaiting_approval'],'resume'=>['from'=>['awaiting_approval'],'to'=>'in_service'],'complete'=>['from'=>['in_service','awaiting_approval'],'to'=>'completed'],'cancel'=>['from'=>['scheduled','confirmed','traveling','arrived','awaiting_approval'],'to'=>'cancelled']];
    public function __construct(private AuditService $audit){}
    public function transition(WorkOrder $order,string $action,?string $notes=null): WorkOrder { $rule=self::TRANSITIONS[$action]??null;if(!$rule||!in_array($order->status,$rule['from'],true))throw ValidationException::withMessages(['action'=>'Transição inválida para o estado atual da OS.']);return DB::transaction(function()use($order,$rule,$action,$notes){$from=$order->status;$changes=['status'=>$rule['to']];if($action==='start_service')$changes['started_at']=now();if($action==='complete')$changes['finished_at']=now();$order->update($changes);WorkOrderStatusHistory::create(['work_order_id'=>$order->id,'user_id'=>auth()->id(),'from_status'=>$from,'to_status'=>$rule['to'],'occurred_at'=>now(),'notes'=>$notes]);if($order->appointment)$order->appointment->update(['status'=>$rule['to']]);$this->audit->record('work_order_status_changed',$order,['status'=>$from],['status'=>$rule['to']],$notes);return $order->refresh();}); }
}

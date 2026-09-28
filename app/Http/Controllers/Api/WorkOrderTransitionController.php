<?php
namespace App\Http\Controllers\Api;
use App\Models\WorkOrder;
use App\Services\WorkOrderWorkflowService;
use Illuminate\Http\Request;
class WorkOrderTransitionController { public function __invoke(Request $request,WorkOrder $workOrder,WorkOrderWorkflowService $workflow){$data=$request->validate(['action'=>'required|in:start_travel,arrive,start_service,await_approval,resume,complete,cancel','notes'=>'nullable|string']);if($request->user()->role==='technician'&&$workOrder->technician_id!==$request->user()->employee?->id)abort(403);$workflow->transition($workOrder,$data['action'],$data['notes']??null);return response()->json(['data'=>$workOrder->load(['customer','address','equipment','technician','services','products','payments','statusHistory'])]);} }

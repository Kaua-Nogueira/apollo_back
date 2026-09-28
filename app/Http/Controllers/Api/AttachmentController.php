<?php
namespace App\Http\Controllers\Api;
use App\Models\WorkOrder;
use App\Services\WorkOrderAccessService;
use Illuminate\Http\Request;
class AttachmentController { public function store(Request $request,WorkOrder $workOrder,WorkOrderAccessService $access){$access->ensureCanOperate($request->user(),$workOrder);$data=$request->validate(['photo'=>'required|image|max:10240','category'=>'required|in:before,during,after,equipment,problem,other']);$file=$request->file('photo');$path=$file->store("work-orders/{$workOrder->id}",'public');$attachment=$workOrder->attachments()->create(['uploaded_by'=>$request->user()->id,'category'=>$data['category'],'path'=>$path,'mime_type'=>$file->getMimeType(),'size'=>$file->getSize()]);return response()->json(['data'=>$attachment],201);} }

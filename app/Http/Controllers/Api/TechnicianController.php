<?php
namespace App\Http\Controllers\Api;
use App\Models\Appointment;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
class TechnicianController { public function today(Request $request){$employee=$request->user()->employee;if(!$employee)return response()->json(['message'=>'Usuário sem funcionário vinculado.'],422);$last=TimeEntry::where('employee_id',$employee->id)->whereDate('work_date',now())->latest('occurred_at')->first();$clockStatus=$last?->type==='break_start'?'on_break':(in_array($last?->type,['clock_in','break_end'])?'working':'off');return response()->json(['data'=>['clock_status'=>$clockStatus,'appointments'=>Appointment::with(['customer','address','equipment','technician','workOrder'])->where('technician_id',$employee->id)->whereDate('scheduled_at',now())->orderBy('scheduled_at')->get()]]);} }

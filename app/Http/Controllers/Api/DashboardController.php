<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Equipment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TimeEntry;
use App\Models\WorkOrder;
class DashboardController extends Controller { public function __invoke(){ $today=now()->toDateString();$month=now()->month;$year=now()->year;$total=WorkOrder::sum('total');$received=Payment::sum('amount');return response()->json(['data'=>['metrics'=>['appointments_today'=>Appointment::whereDate('scheduled_at',$today)->count(),'completed_today'=>WorkOrder::whereDate('finished_at',$today)->where('status','completed')->count(),'in_progress'=>WorkOrder::whereIn('status',['traveling','arrived','in_service','awaiting_approval'])->count(),'technicians_working'=>TimeEntry::whereDate('work_date',$today)->where('type','clock_in')->distinct('employee_id')->count('employee_id'),'revenue_today'=>(float)Payment::whereDate('paid_at',$today)->sum('amount'),'revenue_month'=>(float)Payment::whereYear('paid_at',$year)->whereMonth('paid_at',$month)->sum('amount'),'receivable'=>(float)max(0,$total-$received),'overdue'=>0],'upcoming'=>Appointment::with(['customer','address','equipment','technician','workOrder'])->whereDate('scheduled_at',$today)->whereNotIn('status',['cancelled'])->orderBy('scheduled_at')->limit(6)->get(),'preventive_due'=>Equipment::whereDate('next_maintenance_at','<=',now()->addDays(30))->count(),'low_stock'=>Product::whereColumn('stock','<=','minimum_stock')->count()]]); } }

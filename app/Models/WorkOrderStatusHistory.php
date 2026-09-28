<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkOrderStatusHistory extends Model { protected $guarded=[]; protected $table='work_order_status_history'; protected function casts(): array { return ['occurred_at'=>'datetime']; } public function workOrder(){return $this->belongsTo(WorkOrder::class);} }

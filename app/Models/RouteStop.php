<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RouteStop extends Model { protected $guarded=[]; protected function casts():array{return ['planned_at'=>'datetime','arrived_at'=>'datetime','completed_at'=>'datetime'];} public function appointment(){return $this->belongsTo(Appointment::class);} public function workOrder(){return $this->belongsTo(WorkOrder::class);} }

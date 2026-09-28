<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Appointment extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['scheduled_at'=>'datetime']; } public function customer(){return $this->belongsTo(Customer::class);} public function address(){return $this->belongsTo(CustomerAddress::class);} public function equipment(){return $this->belongsTo(Equipment::class);} public function technician(){return $this->belongsTo(Employee::class,'technician_id')->with('user');} public function workOrder(){return $this->hasOne(WorkOrder::class);} }

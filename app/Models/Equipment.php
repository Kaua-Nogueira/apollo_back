<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Equipment extends Model { use SoftDeletes; protected $guarded=[]; protected $table='customer_equipment'; protected function casts(): array { return ['active'=>'boolean','installed_at'=>'date','purchased_at'=>'date','warranty_until'=>'date','last_maintenance_at'=>'date','next_maintenance_at'=>'date']; } public function customer(){return $this->belongsTo(Customer::class);} public function address(){return $this->belongsTo(CustomerAddress::class,'address_id');} public function workOrders(){return $this->hasMany(WorkOrder::class)->latest('scheduled_at');} }

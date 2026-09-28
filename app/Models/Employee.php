<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Employee extends Model { use SoftDeletes; protected $guarded=[]; protected $appends=['name']; protected function casts(): array { return ['active'=>'boolean','hired_at'=>'date']; } public function user(){return $this->belongsTo(User::class);} public function appointments(){return $this->hasMany(Appointment::class,'technician_id');} public function workOrders(){return $this->hasMany(WorkOrder::class,'technician_id');} public function getNameAttribute(){return $this->user?->name;} }

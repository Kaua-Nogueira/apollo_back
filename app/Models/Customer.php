<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['active'=>'boolean']; } public function addresses(){return $this->hasMany(CustomerAddress::class);} public function equipment(){return $this->hasMany(Equipment::class);} public function workOrders(){return $this->hasMany(WorkOrder::class);} }

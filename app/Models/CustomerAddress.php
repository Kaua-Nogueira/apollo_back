<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CustomerAddress extends Model { use SoftDeletes; protected $guarded=[]; protected $table='customer_addresses'; public function customer(){return $this->belongsTo(Customer::class);} public function equipment(){return $this->hasMany(Equipment::class,'address_id');} }

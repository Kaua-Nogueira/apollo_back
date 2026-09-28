<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Receivable extends Model { protected $guarded=[]; protected function casts():array{return ['due_date'=>'date','amount'=>'decimal:2','received'=>'decimal:2'];} public function workOrder(){return $this->belongsTo(WorkOrder::class);} public function customer(){return $this->belongsTo(Customer::class);} }

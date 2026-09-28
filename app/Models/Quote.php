<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Quote extends Model { use SoftDeletes; protected $guarded=[]; protected function casts():array{return ['discount'=>'decimal:2','total'=>'decimal:2','valid_until'=>'date'];} public function customer(){return $this->belongsTo(Customer::class);} public function address(){return $this->belongsTo(CustomerAddress::class);} public function items(){return $this->hasMany(QuoteItem::class);} }

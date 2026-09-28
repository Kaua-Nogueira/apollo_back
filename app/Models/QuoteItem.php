<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuoteItem extends Model { protected $guarded=[]; protected function casts():array{return ['quantity'=>'decimal:3','unit_price'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2'];} }

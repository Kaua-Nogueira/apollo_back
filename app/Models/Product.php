<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['cost_price'=>'decimal:2','sale_price'=>'decimal:2','stock'=>'decimal:3','minimum_stock'=>'decimal:3','active'=>'boolean']; } }

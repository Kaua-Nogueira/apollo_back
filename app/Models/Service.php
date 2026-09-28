<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Service extends Model { use SoftDeletes; protected $guarded=[]; protected function casts(): array { return ['default_price'=>'decimal:2','estimated_cost'=>'decimal:2','active'=>'boolean']; } }

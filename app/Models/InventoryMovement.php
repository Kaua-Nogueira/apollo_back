<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model { protected $guarded=[]; protected function casts(): array{return ['quantity'=>'decimal:3','balance_after'=>'decimal:3','occurred_at'=>'datetime'];} }

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model { protected $guarded=[]; protected function casts(): array{return ['old_values'=>'array','new_values'=>'array'];} public function user(){return $this->belongsTo(User::class);} }

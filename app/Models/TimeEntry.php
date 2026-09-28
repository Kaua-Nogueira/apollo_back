<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TimeEntry extends Model { protected $guarded=[]; protected function casts(): array{return ['work_date'=>'date','occurred_at'=>'datetime','adjusted'=>'boolean'];} public function employee(){return $this->belongsTo(Employee::class);} }

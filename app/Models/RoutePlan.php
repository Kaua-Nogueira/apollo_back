<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RoutePlan extends Model { use SoftDeletes; protected $guarded=[]; protected $table='routes'; protected function casts():array{return ['route_date'=>'date'];} public function technician(){return $this->belongsTo(Employee::class,'technician_id')->with('user');} public function vehicle(){return $this->belongsTo(Vehicle::class);} public function stops(){return $this->hasMany(RouteStop::class,'route_id')->orderBy('position');} }

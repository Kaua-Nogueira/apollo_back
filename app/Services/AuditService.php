<?php
namespace App\Services;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
class AuditService { public function record(string $action,Model $model,array $old=[],array $new=[],?string $reason=null): void { AuditLog::create(['user_id'=>auth()->id(),'action'=>$action,'auditable_type'=>$model::class,'auditable_id'=>$model->getKey(),'old_values'=>$old?:null,'new_values'=>$new?:null,'reason'=>$reason,'ip_address'=>request()->ip()]); } }

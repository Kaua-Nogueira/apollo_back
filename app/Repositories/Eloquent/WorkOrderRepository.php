<?php
namespace App\Repositories\Eloquent;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
class WorkOrderRepository implements WorkOrderRepositoryInterface { private array $relations=['customer','address','equipment','technician','services','products','payments','attachments','statusHistory']; public function paginate(array $filters=[]): LengthAwarePaginator{return WorkOrder::query()->with($this->relations)->when($filters['status']??null,fn($q,$v)=>$q->where('status',$v))->when($filters['technician_id']??null,fn($q,$v)=>$q->where('technician_id',$v))->latest('scheduled_at')->paginate($filters['per_page']??50);} public function create(array $data): WorkOrder{return WorkOrder::create($data);} public function find(int $id): WorkOrder{return WorkOrder::with($this->relations)->findOrFail($id);} }

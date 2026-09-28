<?php
namespace App\Repositories\Contracts;
use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
interface WorkOrderRepositoryInterface { public function paginate(array $filters=[]): LengthAwarePaginator; public function create(array $data): WorkOrder; public function find(int $id): WorkOrder; }

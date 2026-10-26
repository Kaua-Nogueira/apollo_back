<?php
namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Customer::query()
            ->with(['addresses', 'equipment'])
            ->withCount('workOrders')
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('name', 'like', "%$v%")
                        ->orWhere('phone', 'like', "%$v%")
                        ->orWhere('whatsapp', 'like', "%$v%")
                        ->orWhere('document', 'like', "%$v%");
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 50);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->refresh();
    }
}

<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\Request;
class CustomerController extends Controller { public function __construct(private CustomerRepositoryInterface $customers,private AuditService $audit){} public function index(Request $request){return $this->customers->paginate($request->only('search','per_page'));} public function store(CustomerRequest $request){$customer=$this->customers->create($request->validated());$this->audit->record('customer_created',$customer,[], $customer->toArray());return response()->json(['data'=>$customer->load(['addresses','equipment'])],201);} public function show(Customer $customer){return response()->json(['data'=>$customer->load(['addresses','equipment.workOrders','workOrders'])]);} public function update(CustomerRequest $request,Customer $customer){$old=$customer->toArray();$customer=$this->customers->update($customer,$request->validated());$this->audit->record('customer_updated',$customer,$old,$customer->toArray());return response()->json(['data'=>$customer]);} public function destroy(Customer $customer){$customer->delete();$this->audit->record('customer_archived',$customer);return response()->noContent();} }

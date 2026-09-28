<?php
namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReferenceDataController {
    public function addresses(Customer $customer){return response()->json(['data'=>$customer->addresses()->with('equipment')->get()]);}
    public function storeAddress(Request $r,Customer $customer){$data=$r->validate(['label'=>'required|string|max:100','zip_code'=>'nullable|string|max:15','street'=>'required|string|max:150','number'=>'required|string|max:30','complement'=>'nullable|string|max:100','district'=>'required|string|max:100','city'=>'required|string|max:100','state'=>'required|string|size:2','latitude'=>'nullable|numeric','longitude'=>'nullable|numeric']);return response()->json(['data'=>$customer->addresses()->create($data)],201);}
    public function updateAddress(Request $r,CustomerAddress $address){$data=$r->validate(['label'=>'sometimes|required|string','zip_code'=>'nullable|string','street'=>'sometimes|required|string','number'=>'sometimes|required|string','complement'=>'nullable|string','district'=>'sometimes|required|string','city'=>'sometimes|required|string','state'=>'sometimes|required|string|size:2','latitude'=>'nullable|numeric','longitude'=>'nullable|numeric']);$address->update($data);return response()->json(['data'=>$address]);}
    public function destroyAddress(CustomerAddress $address){$address->delete();return response()->noContent();}
    public function equipment(Request $r){return Equipment::with(['customer','address'])->when($r->customer_id,fn($q,$v)=>$q->where('customer_id',$v))->orderByDesc('next_maintenance_at')->paginate(100);}
    public function showEquipment(Equipment $equipment){return response()->json(['data'=>$equipment->load(['customer','address','workOrders.services','workOrders.products','workOrders.statusHistory'])]);}
    public function storeEquipment(Request $r){$data=$this->equipmentRules($r);return response()->json(['data'=>Equipment::create($data)->load(['customer','address'])],201);}
    public function updateEquipment(Request $r,Equipment $equipment){$equipment->update($this->equipmentRules($r,false));return response()->json(['data'=>$equipment->fresh()->load(['customer','address'])]);}
    public function destroyEquipment(Equipment $equipment){$equipment->delete();return response()->noContent();}
    private function equipmentRules(Request $r,bool $required=true):array{return $r->validate(['customer_id'=>($required?'required':'sometimes').'|exists:customers,id','address_id'=>'nullable|exists:customer_addresses,id','location'=>($required?'required':'sometimes').'|string|max:100','type'=>($required?'required':'sometimes').'|string|max:50','brand'=>'nullable|string|max:80','model'=>'nullable|string|max:80','btus'=>'nullable|integer|min:1','voltage'=>'nullable|string|max:30','serial_number'=>'nullable|string|max:80','refrigerant_gas'=>'nullable|string|max:40','installed_at'=>'nullable|date','purchased_at'=>'nullable|date','warranty_until'=>'nullable|date','maintenance_interval_months'=>'nullable|integer|min:1|max:60','last_maintenance_at'=>'nullable|date','next_maintenance_at'=>'nullable|date','notes'=>'nullable|string','active'=>'sometimes|boolean']);}
    public function employees(){return Employee::with('user')->orderBy('id')->paginate(100);}
    public function storeEmployee(Request $r){$data=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|unique:users,email','password'=>'nullable|string|min:6','phone'=>'nullable|string|max:30','specialty'=>'nullable|string|max:100','hired_at'=>'nullable|date','active'=>'sometimes|boolean']);$employee=DB::transaction(function()use($data){$user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($data['password']??'password'),'role'=>'technician','active'=>$data['active']??true]);return Employee::create(['user_id'=>$user->id,'phone'=>$data['phone']??null,'specialty'=>$data['specialty']??null,'hired_at'=>$data['hired_at']??null,'active'=>$data['active']??true]);});return response()->json(['data'=>$employee->load('user')],201);}
    public function updateEmployee(Request $r,Employee $employee){$data=$r->validate(['name'=>'sometimes|required|string|max:120','email'=>'sometimes|required|email|unique:users,email,'.$employee->user_id,'phone'=>'nullable|string|max:30','specialty'=>'nullable|string|max:100','hired_at'=>'nullable|date','active'=>'sometimes|boolean']);$employee->user->update(array_filter(['name'=>$data['name']??null,'email'=>$data['email']??null],fn($v)=>$v!==null));$employee->update(collect($data)->only(['phone','specialty','hired_at','active'])->all());return response()->json(['data'=>$employee->fresh()->load('user')]);}
    public function destroyEmployee(Employee $employee){$employee->update(['active'=>false]);$employee->user->update(['active'=>false]);$employee->delete();return response()->noContent();}
    public function services(){return Service::orderBy('name')->paginate(100);}
    public function storeService(Request $r){return response()->json(['data'=>Service::create($this->serviceRules($r))],201);}
    public function updateService(Request $r,Service $service){$service->update($this->serviceRules($r,false));return response()->json(['data'=>$service]);}
    public function destroyService(Service $service){$service->delete();return response()->noContent();}
    private function serviceRules(Request $r,bool $required=true):array{return $r->validate(['name'=>($required?'required':'sometimes').'|string|max:120','category'=>'nullable|string|max:80','description'=>'nullable|string','default_price'=>($required?'required':'sometimes').'|numeric|min:0','estimated_cost'=>'nullable|numeric|min:0','average_minutes'=>'nullable|integer|min:1','active'=>'sometimes|boolean']);}
    public function products(){return Product::orderBy('name')->paginate(100);}
    public function storeProduct(Request $r){return response()->json(['data'=>Product::create($this->productRules($r))],201);}
    public function updateProduct(Request $r,Product $product){$product->update($this->productRules($r,false));return response()->json(['data'=>$product]);}
    public function destroyProduct(Product $product){$product->delete();return response()->noContent();}
    private function productRules(Request $r,bool $required=true):array{return $r->validate(['name'=>($required?'required':'sometimes').'|string|max:120','category'=>'nullable|string|max:80','unit'=>($required?'required':'sometimes').'|string|max:20','brand'=>'nullable|string|max:80','code'=>'nullable|string|max:80|unique:products,code,'.($r->route('product')?->id??''),'cost_price'=>($required?'required':'sometimes').'|numeric|min:0','sale_price'=>($required?'required':'sometimes').'|numeric|min:0','stock'=>($required?'required':'sometimes').'|numeric|min:0','minimum_stock'=>($required?'required':'sometimes').'|numeric|min:0','active'=>'sometimes|boolean']);}
}

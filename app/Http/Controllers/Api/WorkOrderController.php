<?php
namespace App\Http\Controllers\Api;
use App\Http\Requests\WorkOrderRequest;
use App\Models\Product;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderProduct;
use App\Models\WorkOrderService;
use App\Models\WorkOrderStatusHistory;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\ReceivableService;
use App\Services\WorkOrderAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class WorkOrderController {
    public function __construct(private WorkOrderRepositoryInterface $orders,private InventoryService $inventory,private AuditService $audit,private ReceivableService $receivables,private WorkOrderAccessService $access){}
    public function index(Request $request){$filters=$request->only('status','technician_id','per_page');if($request->user()->role==='technician')$filters['technician_id']=$request->user()->employee?->id;return $this->orders->paginate($filters);}
    public function store(WorkOrderRequest $request){$data=$request->validated();$order=DB::transaction(function()use($data){$services=$data['services']??[];$products=$data['products']??[];unset($data['services'],$data['products']);$data['number']=$this->nextNumber();$order=$this->orders->create($data);WorkOrderStatusHistory::create(['work_order_id'=>$order->id,'user_id'=>auth()->id(),'to_status'=>'scheduled','occurred_at'=>now()]);foreach($services as $item)$this->addServiceRow($order,$item);foreach($products as $item)$this->addProductRow($order,$item);$this->recalculate($order);return $order;});$this->receivables->sync($order);return response()->json(['data'=>$this->orders->find($order->id)],201);}
    public function show(Request $request,int $id){$order=$this->orders->find($id);if($request->user()->role==='technician'&&$order->technician_id!==$request->user()->employee?->id)abort(403);return response()->json(['data'=>$order]);}
    public function update(Request $request,WorkOrder $workOrder){$this->access->ensureCanOperate($request->user(),$workOrder);$data=$request->validate(['technician_id'=>'sometimes|nullable|exists:employees,id','scheduled_at'=>'sometimes|date','problem_reported'=>'nullable|string','diagnosis'=>'nullable|string','solution'=>'nullable|string','recommendations'=>'nullable|string','notes'=>'nullable|string','discount'=>'sometimes|numeric|min:0']);if($request->user()->role==='technician')unset($data['technician_id'],$data['scheduled_at'],$data['discount']);$old=$workOrder->only(array_keys($data));$workOrder->update($data);$this->recalculate($workOrder);$this->receivables->sync($workOrder);$this->audit->record('work_order_updated',$workOrder,$old,$data);return response()->json(['data'=>$this->orders->find($workOrder->id)]);}
    public function destroy(Request $request,WorkOrder $workOrder){abort_unless($request->user()->role==='admin',403);$this->audit->record('work_order_archived',$workOrder,$workOrder->toArray());$workOrder->delete();return response()->noContent();}
    public function addService(Request $request,WorkOrder $workOrder){$this->access->ensureCanOperate($request->user(),$workOrder);$this->addServiceRow($workOrder,$request->validate(['service_id'=>'required|exists:services,id','quantity'=>'required|numeric|min:.01','unit_price'=>'required|numeric|min:0','discount'=>'nullable|numeric|min:0']));$this->recalculate($workOrder);$this->receivables->sync($workOrder);return response()->json(['data'=>$this->orders->find($workOrder->id)],201);}
    public function addProduct(Request $request,WorkOrder $workOrder){$this->access->ensureCanOperate($request->user(),$workOrder);$this->addProductRow($workOrder,$request->validate(['product_id'=>'required|exists:products,id','quantity'=>'required|numeric|min:.001','unit_price'=>'required|numeric|min:0','discount'=>'nullable|numeric|min:0']));$this->recalculate($workOrder);$this->receivables->sync($workOrder);return response()->json(['data'=>$this->orders->find($workOrder->id)],201);}
    private function addServiceRow(WorkOrder $order,array $item): void {$service=Service::findOrFail($item['service_id']);$quantity=(float)$item['quantity'];$discount=(float)($item['discount']??0);WorkOrderService::create([...$item,'work_order_id'=>$order->id,'name'=>$service->name,'discount'=>$discount,'total'=>max(0,$quantity*(float)$item['unit_price']-$discount)]);}
    private function addProductRow(WorkOrder $order,array $item): void {$product=Product::findOrFail($item['product_id']);$quantity=(float)$item['quantity'];$discount=(float)($item['discount']??0);$this->inventory->consume($product,$quantity,$order);WorkOrderProduct::create([...$item,'work_order_id'=>$order->id,'name'=>$product->name,'discount'=>$discount,'total'=>max(0,$quantity*(float)$item['unit_price']-$discount)]);}
    private function recalculate(WorkOrder $order): void {$subtotal=(float)$order->services()->sum('total')+(float)$order->products()->sum('total');$order->update(['subtotal'=>$subtotal,'total'=>max(0,$subtotal-(float)$order->discount)]);}
    private function nextNumber(): string {$next=(WorkOrder::withTrashed()->max('id')??0)+1;return 'OS-'.now()->format('Ym').'-'.str_pad((string)$next,4,'0',STR_PAD_LEFT);}
}

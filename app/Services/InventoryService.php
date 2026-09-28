<?php
namespace App\Services;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryService { public function consume(Product $product,float $quantity,WorkOrder $workOrder): void { DB::transaction(function()use($product,$quantity,$workOrder){$product=Product::lockForUpdate()->findOrFail($product->id);if((float)$product->stock<$quantity)throw ValidationException::withMessages(['quantity'=>'Estoque insuficiente para '.$product->name]);$product->decrement('stock',$quantity);InventoryMovement::create(['product_id'=>$product->id,'work_order_id'=>$workOrder->id,'user_id'=>auth()->id(),'type'=>'work_order_use','quantity'=>-$quantity,'balance_after'=>$product->fresh()->stock,'occurred_at'=>now()]);});} }

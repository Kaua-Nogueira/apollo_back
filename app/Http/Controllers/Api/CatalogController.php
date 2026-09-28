<?php
namespace App\Http\Controllers\Api;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
class CatalogController { public function services(){return Service::orderBy('name')->paginate(100);} public function products(){return Product::orderBy('name')->paginate(100);} public function storeService(Request $r){return response()->json(['data'=>Service::create($r->validate(['name'=>'required','category'=>'nullable','description'=>'nullable','default_price'=>'required|numeric','estimated_cost'=>'nullable|numeric','average_minutes'=>'nullable|integer','active'=>'boolean']))],201);} public function storeProduct(Request $r){return response()->json(['data'=>Product::create($r->validate(['name'=>'required','category'=>'nullable','unit'=>'required','brand'=>'nullable','code'=>'nullable|unique:products','cost_price'=>'required|numeric','sale_price'=>'required|numeric','stock'=>'required|numeric','minimum_stock'=>'required|numeric','active'=>'boolean']))],201);} }

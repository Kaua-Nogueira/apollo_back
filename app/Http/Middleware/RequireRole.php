<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RequireRole { public function handle(Request $request,Closure $next,string ...$roles): Response { if(!in_array($request->user()?->role,$roles,true))return response()->json(['message'=>'Você não tem permissão para esta ação.'],403);return $next($request); } }

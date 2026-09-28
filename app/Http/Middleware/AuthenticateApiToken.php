<?php
namespace App\Http\Middleware;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
class AuthenticateApiToken { public function handle(Request $request,Closure $next): Response { $plain=$request->bearerToken();$user=$plain?User::where('api_token',hash('sha256',$plain))->where('active',true)->first():null;if(!$user)return response()->json(['message'=>'Sessão inválida ou expirada.'],401);Auth::setUser($user);$request->setUserResolver(fn()=>$user);return $next($request); } }

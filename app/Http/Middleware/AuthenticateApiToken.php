<?php
namespace App\Http\Middleware;
use App\Models\User;
use App\Models\UserApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
class AuthenticateApiToken { public function handle(Request $request,Closure $next): Response { $plain=$request->bearerToken();$hash=$plain?hash('sha256',$plain):null;$token=$hash?UserApiToken::where('token_hash',$hash)->with('user')->first():null;$user=$token?->user;if(!$user&&$hash)$user=User::where('api_token',$hash)->first();if(!$user||!$user->active)return response()->json(['message'=>'Sessão inválida ou expirada.'],401);if($token)$request->attributes->set('api_token_id',$token->id);else $request->attributes->set('legacy_api_token_hash',$hash);Auth::setUser($user);$request->setUserResolver(fn()=>$user);return $next($request); } }

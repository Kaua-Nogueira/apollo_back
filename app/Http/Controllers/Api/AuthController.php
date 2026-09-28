<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class AuthController extends Controller { public function login(Request $request){$credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);$user=User::with('employee')->where('email',$credentials['email'])->where('active',true)->first();if(!$user||!Hash::check($credentials['password'],$user->password))return response()->json(['message'=>'E-mail ou senha incorretos.'],422);$token=Str::random(64);$user->forceFill(['api_token'=>hash('sha256',$token)])->save();return response()->json(['data'=>['token'=>$token,'user'=>$user]]);} public function me(Request $request){return response()->json(['data'=>$request->user()->load('employee')]);} public function logout(Request $request){$request->user()->forceFill(['api_token'=>null])->save();return response()->json(['message'=>'Sessão encerrada.']);} }

<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class AuthController extends Controller { public function login(Request $request){$credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);$user=User::with('employee')->where('email',$credentials['email'])->where('active',true)->first();if(!$user||!Hash::check($credentials['password'],$user->password))return response()->json(['message'=>'E-mail ou senha incorretos.'],422);$token=Str::random(64);UserApiToken::create(['user_id'=>$user->id,'token_hash'=>hash('sha256',$token)]);return response()->json(['data'=>['token'=>$token,'user'=>$user]]);} public function me(Request $request){return response()->json(['data'=>$request->user()->load('employee')]);} public function logout(Request $request){$tokenId=$request->attributes->get('api_token_id');if($tokenId){UserApiToken::where('user_id',$request->user()->id)->whereKey($tokenId)->delete();}else{$legacyHash=$request->attributes->get('legacy_api_token_hash');if($legacyHash)$request->user()->newQuery()->whereKey($request->user()->id)->where('api_token',$legacyHash)->update(['api_token'=>null]);}return response()->json(['message'=>'Sessão encerrada.']);} }

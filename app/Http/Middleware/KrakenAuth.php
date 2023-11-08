<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class KrakenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $uncrypt = Crypt::decryptString($request->bearerToken());// validamos / decodificamos token
            $request->fixeds = json_decode($uncrypt);// guardamos en el request lo desencriptado [uid(userid), rol(id), store(id), ini(token), exp(token)]

            // expiracion $exp = $request->exp;
            $uid = $request->fixeds->uid;
            $state = User::find($uid)->_state;

            switch($state){
                case 3: case 4: return response("Kraken: You have been banned!",511); break;
                case 5: return response("Kraken: Restart session required!",511); break;
                default: return $next($request); break;
            }
        } catch (DecryptException $e) { return response($e,511); }
    }
}

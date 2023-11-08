<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserStores;

class UseStore
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, )
    {
        $sid = $request->route('sid');
        $uid = $request->fixeds->uid;

        $access = UserStores::where([ ['_user',$uid], ['_store',$sid] ])->first();
        if($access && $access->_state==1){
            return $next($request);
        }else{ return response()->json("Acceso restringido",401); }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class UseProviders
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
        // $id = $request->query('id');
        $id = $request->fixeds->uid;
        $user = User::find($id)->modules()->where('_module','42c9')->first();
        if($user){
            return $next($request);
        }else{
            return response()->json("OIE PADRINO NO TIENES PERMISO  SAQUESE DE AQUI XD",405);
        }
    }
}

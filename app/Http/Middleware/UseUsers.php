<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;

class UseUsers
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
        $id = $request->query('id');
        $user = User::find($id)->modules()->where('_module','4f36')->first();
        if($user){
            return $next($request);
        }else{
            return response()->json("OIE PADRINO NO TIENES PERMISO  SAQUESE DE AQUI XD",405);
        }

    }
}

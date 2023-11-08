<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class UseWarehouse
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
        $sid = $request->route('sid'); // id de la sucursal
        $wid = $request->route('wid'); // id del almacen
        $rol = $request->fixeds->rol; // rol del usuario conectado

        $wrh = Warehouse::find($wid);

        if($wrh->_store == $sid){// validamos que el almacen pertenezca a la tienda de donde el usuario esta coinectado
            if(($rol!=12) && ($rol!=16)){// el usuario no es bodeguero ni ventas
                return $next($request);
            }else{
                // validar que: bodeguero (rol 12) acdeda solo a almacen ventas (type 1) && vendedor (rol 16) acceda solo a exhibicion (type 2)
                if(($rol==12&&$wrh->_type==1) || ($rol==16&&$wrh->_type==2)){
                    return $next($request);
                } return response("No puedes usar este almacen",401);
            }
        } return response("No puedes usar este almacen",401);
    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\RestockOrder;
use App\Models\RestockTypes;
use App\Models\ProductStock;
use App\Models\RestockStates;
use App\Models\ProductLocation;

class Elo extends Controller {
    public function index(Request $request){
        $nick = "crack";

        $user = User::where('nick',$nick)->orWhere('celphone',$nick)->orWhere('email',$nick)->first();

        $user->load([
            'rol',
            'state',
            'store',
            'stores',
            'modules' => fn($q) => $q->with([ 'permission', 'module' ])
        ]);

        // dd($user);

        return $user;
    }
}

/**
 * hay dos formas de extraer / visualizar los SQLs generados por eloquent
 */

 /**
 * Usar el Metodo ->toSql, este devolvera el query que construyo, por tanto no lo ejecuta
 *
 * $users = User::where("id",">",2)->toSql();
 *
 * dd($users);
 */

 /**
 * La segunda forma es despues de haber ejecutado el query
 *
 * DB::enableQueryLog();
 * $pdss = User::get();
 * dd(DB::getQueryLog());
 */

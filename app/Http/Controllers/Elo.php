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
use App\Models\TransferBW;
use App\Models\TransferBWProduct;

class Elo extends Controller {
    public function index(Request $request){
        // $store = 1;
        // $init = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");
        // $end = Carbon::now()->endOfDay()->format("Y-m-d H:i:s");
        $tid = '9';
        $product = 2;
        $uid = 1;

        $row = TransferBWProduct::where([
            ["_transfer",$tid],
            ["_product",$product],
            ["_user",$uid],
        ])->first();

        // dd($row);
        // return $transfers;
        return true;
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

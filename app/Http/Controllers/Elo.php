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
    public function __invoke(Request $request){
        // $store = 1;
        // $init = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");
        // $end = Carbon::now()->endOfDay()->format("Y-m-d H:i:s");
        $wid = 26;
        $product = 2;
        $uid = 1;
        $model = [ "_product"=>8841, "_location"=>249 ];

        $q = ProductLocation::with([ "product", "location" ])->where($model)->get();

        // $q->load([ "product" ]);

        // $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([ ["_warehouse",$wid] ]); });

        // $data = $query->get()->load([
        //     "relateds",
        //     "unitsupply",
        //     "stock" => fn($q) => $q->where('_warehouse',$wid),
        //     "locations" => fn($q) => $q->where('_warehouse',$wid)
        // ]);

        dd($q);
        // return $q;
        // return true;
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

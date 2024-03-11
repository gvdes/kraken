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
use App\Models\Warehouse;
use App\Models\ProductCategory;
use App\Models\Seasons;
use App\Models\StoresSeasons;

class Elo extends Controller {

    public function __invoke(Request $request){
        // $store = 1;
        // $init = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");
        // $end = Carbon::now()->endOfDay()->format("Y-m-d H:i:s");
        $store = 7;
        $wid = 7;
        $sid = 7;
        $product = 2;
        $uid = 1;
        $model = [ "_product"=>8841, "_location"=>249 ];

        $seasons = StoresSeasons::with([ "category" ])->where([ ["_store",$sid], ["_state",1] ])->get();
        $season_cats = $seasons->map(function($e) {
            $id = $e->_season;
            $children = DB::select('CALL categoriesOf(?)', [$id]);
            return [ "parent"=>$e, "children"=>$children ];
        });

        $idsparents = $season_cats->map(fn($sc) => $sc["parent"]->_season );
        $idschildren = $season_cats->map(fn($sc) => $sc["children"])->flatten()->map(fn($c) => $c->id);
        $idscats = $idsparents->merge($idschildren);

        $products = Product::whereHas("stocks", function($q) use($wid){ $q->where([ ["_warehouse",$wid],["_state",1] ]); })->whereIn("_category",$idscats)->get();
        // $products = Product::whereIn("_category",$idscats)->get();

        // $ids_cats = $cats->map(fn($sc) => $sc["tree"]);
        // echo gettype($seasons);
        // dd($parents);
        // return true;
        return count($products);
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

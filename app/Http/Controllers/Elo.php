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

        $store = 1;
        $wrhReq = 1;
        $wrhSup = 1;
        $vswid = 1;
        $product = 2;
        $uid = 1;
        $model = [ "_product"=>8841, "_location"=>249 ];
        // $items = ProductStock::with(["state"])->whereIn("_warehouse",[$wid,$vswid])->whereIn("_product",$pids)->get();
        $seasons = $this->season($store);
        $seasonsids = $seasons["ids"];
        $ids_wrhs_comp = [1,2,4,92];

        $stockWarehouse = ProductStock::with(["product"])->where([
            ["_state",1],
            ["_min",">",0],
            ["available","<=","_min"],
            ["_warehouse",$wrhReq]
        ])->whereHas("product", function($q) use($seasonsids){
            $q->where("_state",1)->whereIn("_category",$seasonsids);
        })->withSum([
            "stocksProduct" => function($q) use($ids_wrhs_comp){
                return $q->whereIn("_warehouse",$ids_wrhs_comp);
            }],"available")
        ->having('stocks_product_sum_available', '>', "product_stock._min")
        ->get();

        // $prods = ProductStock::whereIn("_warehouse",[1])->where("_product",1)->sum("available");

        // dd($stockWarehouse);
        // return $seasons["ids"];
        return true;
        // return count($items);
    }

    private function season($sid){
        // obtenemos las temporadas de la tienda con su categoria
        $seasons = StoresSeasons::with([ "category" ])->where([ ["_store",$sid], ["_state",1] ])->get();

        // iteramos las temporadas para obtener las subcategorias de cada una
        $season_cats = $seasons->map(function($e) {
            $id = $e->_season; // id de la categoria raiz
            $children = DB::select('CALL categoriesOf(?)', [$id]); // subcategoriad de la categoria raiz
            return [ "parent"=>$e, "children"=>$children ];
        });

        // Creamos la lista completa de las categorias de la temporada
        $idsp = $season_cats->map(fn($sc) => $sc["parent"]->_season );// ids de las categorias padre
        $idsc = $season_cats->map(fn($sc) => $sc["children"])->flatten()->map(fn($c) => $c->id);// ids de las categorias hijas
        $ids_cats = $idsp->merge($idsc)->toArray(); // lista completa de ids de las categorias en las temporadas

        return [
            "cats" => $season_cats,
            "ids" => $ids_cats
        ];
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

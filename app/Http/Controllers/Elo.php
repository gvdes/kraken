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
        $wrhReq = 18;
        $wrhSup = 1;
        $vswid = 1;
        $product = 2;
        $uid = 1;
        $model = [ "_product"=>8841, "_location"=>249 ];
        // $items = ProductStock::with(["state"])->whereIn("_warehouse",[$wid,$vswid])->whereIn("_product",$pids)->get();

        $season = $this->season($store); // temporadas de la sucursal
        $categories = $season["ids"];
        $categoryPlaceholders = implode(',', array_fill(0, count($categories), '?'));
        $parameters = array_merge([$wrhSup, $wrhReq], $categories);

        $sto_prod_dest = 'SELECT
            P.`code` AS "product_code",
            P.`short_code` AS "product_shortcode",
            P.`id` AS "product_id",
            P.`_state` AS "state_incat",
            CST.`name` AS "state_incat_name",
            stoReq.`_product` AS "sto_product_id",
            stoReq.`_current` AS "current",
            stoReq.`available` AS "available",
            stoReq.`in_coming` AS "transit",
            stoReq.`_min` AS "stock_min",
            stoReq.`_max` AS "stock_max",
            stoReq.`_state` AS "state_inwrh",
            WST.`name` AS "state_inwrh_name",
            stoDest.`_current` AS "current_dest",
            stoDest.`available` AS "available_dest"
        FROM product_stock stoReq
            INNER JOIN products P ON P.`id` = stoReq.`_product`
            INNER JOIN product_states CST ON CST.`id` = P.`_state`
            INNER JOIN product_states WST ON WST.`id` = stoReq.`_state`
            INNER JOIN product_stock stoDest ON (stoDest.`_warehouse` = ? AND stoDest.`_product` = stoReq.`_product`)
        WHERE
            stoReq.`_warehouse` = ? AND
            (stoReq.`_min`>0 OR stoReq.`_max`>0) AND
            P.`_category` IN ('.$categoryPlaceholders.');
        ';

        try {
            //code...
            $sto_prod_dest = DB::select($sto_prod_dest,$parameters);
            return sizeof($sto_prod_dest);
            // dd($sto_prod_dest);
        } catch (\Throwable $th) {
            return $th;
        }


        // dd($items);
        // return $products;
        // return true;
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

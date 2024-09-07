<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoresSeasons;
use Illuminate\Support\Facades\DB;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Store;

class ComparatorWarehouse extends Controller
{
    private $reports = [
        "A" => "rep_min_and_max",
        "B" => "rep_models_miss"
    ];

    public function index(Request $request){
        $sid = $request->route('sid');
        $wid = $request->route('wid');
        $rol = $request->fixeds->rol;

        $stores = Store::with([
            "warehouses" => fn($q) => $q->with(['type']),
            "type"
        ])->get();

        $resp = [
            "stores" => $stores
        ];

        return response()->json($resp);
    }

    public function report(Request $request){
        $sid = $request->route('sid'); // id de la tienda origen
        $wrhReq = $request->route('wid'); // id del almacen de la tiend origen
        $report = $request->route('repid');


        $season = $this->season($sid); // temporadas de la sucursal

        $func = $this->reports[$report]; // reporte a generar
        $respReport = $this->$func($wrhReq,$season["ids"],$sid);

        $resp = [
            "respReport"=>$respReport,
            "repid" => $report,
            "wrhReq" => $wrhReq,
            // "products" => $products,
            "season" => $season
        ];

        return response()->json($resp);
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

    private function rep_min_and_max($wrhReq,$ids_cats,$store){

        $warehouses = Warehouse::where([ ["_store",1], ["_type",1], ["_state",1] ])->get();

        // $wrhReqs = array_merge([$wrhReq],collect($warehouses)->map(function($w){ return $w->id; })->toArray());

        // $names_cols =

        // $categoryPlaceholders = implode(',', array_fill(0, count($ids_cats), '?'));
        // $parameters = array_merge([$wrhSup, $wrhReq], $ids_cats);

        // $query = 'SELECT
        //     P.`code` AS "product_code",
        //     P.`short_code` AS "product_shortcode",
        //     P.`id` AS "product_id",
        //     P.`description` AS "product_desc",
        //     P.`_state` AS "state_incat",
        //     CST.`name` AS "state_incat_name",
        //     stoReq.`_product` AS "sto_product_id",
        //     stoReq.`_current` AS "stock_current",
        //     stoReq.`available` AS "stock_available",
        //     stoReq.`in_coming` AS "stock_transit",
        //     stoReq.`_min` AS "stock_min",
        //     stoReq.`_max` AS "stock_max",
        //     stoReq.`_state` AS "state_inwrh",
        //     WST.`name` AS "state_inwrh_name",
        //     stoDest.`_current` AS "dest_current",
        //     stoDest.`available` AS "dest_available"
        // FROM product_stock stoReq
        //     INNER JOIN products P ON P.`id` = stoReq.`_product`
        //     INNER JOIN product_states CST ON CST.`id` = P.`_state`
        //     INNER JOIN product_states WST ON WST.`id` = stoReq.`_state`
        //     INNER JOIN product_stock stoDest ON (stoDest.`_warehouse` = ? AND stoDest.`_product` = stoReq.`_product`)
        // WHERE
        //     stoReq.`_warehouse` = ? AND
        //     (stoReq.`_min`>0 OR stoReq.`_max`>0) AND
        //     stoReq.`_state` = 1 AND
        //     P.`_category` IN ('.$categoryPlaceholders.');
        // ';

        return [ "almacenes" => $warehouses];
        // return DB::select($query,$parameters);
    }

    private function rep_models_miss($store,$wrh,$ids_cats){
        return [];
    }
}

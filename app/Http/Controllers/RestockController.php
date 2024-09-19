<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\ProductStock;
use App\Models\RestockOrder;
use Illuminate\Http\Request;
use App\Models\RestockStates;
use App\Models\RestockTypes;
use App\Models\Warehouse;
use App\Models\Store;
use App\Models\Product;
use App\Models\StoresSeasons;
use Carbon\Carbon;

class RestockController extends Controller
{
    private $previews = [
        "A" => "prev_min_max",
        "B" => "prev_models_miss"
    ];

    public function index(Request $request){
        $sid = $request->route('sid');
        $_init = $request->query('init') ? $request->query('init') : Carbon::now();
        $_end = $request->query('end') ? $request->query('end') : Carbon::now();

        $init = Carbon::parse($_init)->startOfDay()->format("Y-m-d H:i:s");
        $end = Carbon::parse($_end)->endOfDay()->format("Y-m-d H:i:s");

        $states = RestockStates::all();
        $reqTypes = RestockTypes::all();
        $stores = Store::with([
            "warehouses" => fn($q) => $q->with(['type']),
            "type"
        ])->get();

        $orders = RestockOrder::with([ "owner", "state", "fromStore", "toStore" ])
                    ->where(function($q) use($sid){ $q->where("_store_from",$sid)->orWhere("_store_to",$sid); })
                    ->whereBetween("created_at",[$init,$end])
                    ->get();

        return response()->json([
            "stores"=>$stores,
            "states" => $states,
            "reqTypes"=>$reqTypes,
            "_init" => $_init,
            "_end" => $_end,
            "init" => $init,
            "end" => $end,
            "orders"=>$orders
        ]);
    }

    public function create(Request $request){
        $sid = $request->route('sid');
        $to = $request->origin;
        $uid = $request->fixeds->uid;

        $init = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");
        $end = Carbon::now()->endOfDay()->format("Y-m-d H:i:s");

        /**
         * nos: Number Order Store (on day)
         * nod: Number Order Day (general)
         * nfs: Nex Consecutive Store
         * nfd: Next Consecutive Day
         */
        $nos = RestockOrder::where(function($q) use($sid){ $q->where("_store_from",$sid)->orWhere("_store_to",$sid); })
            ->whereBetween("created_at",[$init,$end])
            ->count();

        $nod = RestockOrder::whereBetween("created_at",[$init,$end])->count();

        $ncs = ($nos+1);
        $ncd = ($nod+1);

        $neworder = new RestockOrder([
            "num_ticket" => $ncd,
            "num_ticket_store" => $ncs,
            "_created_by" => $uid,
            "_store_from" => $sid,
            "_store_to" => $to,
            "_type" => 1,
            "_state" => 1,
            "printed" => 0
        ]);

        $neworder->save();
        $neworder->load([ "owner", "state", "fromStore", "toStore" ]);

        return response()->json([ "order"=>$neworder ]);
    }

    public function find(Request $request){
        $rid = $request->route('rid');
        $sid = $request->route('sid');

        $order = RestockOrder::findOrFail($rid);

        $order->load([ "owner", "state", "fromStore", "toStore" ]); // falta incluir el log

        if($order->_store_from == $sid){

            return response()->json([ "order"=>$order ]);
        }else{ return response("don cross", 401); }
    }

    public function preview (Request $request){
        $sid = $request->route('sid');
        $rid = $request->route('rid');
        $wrhsrc = $request->query("wrhsrc")!="undefined" ? $request->query("wrhsrc") : null; // almacen fuente
        $dynFn = $this->previews[$rid];

        $store = Store::find($sid);
        $seasons = $this->getSeasons($sid);
        $warehouse_req = null;
        $warehouses_comp = null;
        $res_dynfn = null;

        $isACedis = ($store->_type == 1);
        $warehouse_req = Warehouse::where([ ["_type",1], ["_store",$sid] ])->first();

        if($isACedis){
            $warehouse_req = Warehouse::find($wrhsrc);
            $warehouses_comp = Warehouse::where([ ["id","!=",$wrhsrc], ["_type",4], ["_state",1], ["_store", $sid] ])->get();
        }else{
            $warehouse_req = Warehouse::where([ ["_type",1], ["_store", $sid] ])->first();
            $warehouses_comp = Warehouse::where([ ["_type",4], ["_state",1], ["_store", 1] ])->get();
        }

        if($warehouse_req && $warehouses_comp){
            $ids_wrhs_comp = $warehouses_comp->map( fn($w) => $w->id);
            $res_dynfn = $this->$dynFn($warehouse_req->id, $ids_wrhs_comp, $seasons["ids"]);
        }

        return response()->json([
            "store"=>$store,
            "report"=>$rid,
            "isACedis"=>$isACedis,
            "seassons"=>$seasons,
            "warehouses_comp"=>$warehouses_comp,
            "warehouse_req"=>$warehouse_req,
            "resdynfn"=>$res_dynfn,
            "wrhsrc"=>$wrhsrc,
            "ids_wrhs_comp"=>$ids_wrhs_comp
        ]);
    }

    private function prev_min_max($wrhsrc,$ids_wrhs_comp=[],$seasonsids){

        $stockWarehouse = ProductStock::with(["product"])->where([
            ["_state",1],
            ["_min",">",0],
            ["available","<=","_min"],
            ["_warehouse",$wrhsrc]
        ])->whereHas("product", function($q) use($seasonsids){
            $q->where("_state",1)->whereIn("_category",$seasonsids);
        })->withSum([
            "stocksProduct" => function($q) use($ids_wrhs_comp){
                return $q->whereIn("_warehouse",$ids_wrhs_comp);
            }
        ],"available")->get();

        return [
            "wrhFrom"=>$wrhsrc,
            "wrhVs"=>$ids_wrhs_comp,
            "basket"=>$stockWarehouse
        ];
    }

    private function prev_models_miss($wrhsrc,$ids_wrhs_comp=[]){
        $stockWarehouse = [];

        return [
            "wrhFrom"=>$wrhsrc,
            "wrhVs"=>$ids_wrhs_comp,
            "basket"=>$stockWarehouse
        ];
    }

    private function getSeasons($sid){
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

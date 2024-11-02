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
use App\Models\RestockBody;
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

        $store = Store::find($sid);
        $orders = $store->restock()->with([
            "owner",
            "state",
            "originWrh" => fn($q) => $q->with(['store']),
            "sourceWrh" => fn($q) => $q->with(['store']),
            "type"
        ])->whereBetween("created_at",[$init,$end])->get();

        $states = RestockStates::all();
        $reqTypes = RestockTypes::all();
        $stores = Store::with([
            "warehouses" => fn($q) => $q->with(['type']),
            "type"
        ])->get();

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
        $sid = $request->route('sid');// sucursal desde donde se crea el pedido
        $uid = $request->fixeds->uid;// usuario que solicita el pedido
        $restockType = $request->type["id"];
        $wrhTo = $request->to["id"]; // id almacen proveedor
        $storeTo = $request->to["_store"]; // id sucursal proveedora
        $folio = $request->folio; // folio (preventa||factusol)
        $avz_params = $request->avz_params; // parametros para pedidos avanzados
        $wrhFrom = Warehouse::where([ ["_type",1], ["_store",$sid] ])->first()->id;

        switch ($restockType) {
            case 2: $resp = $this->createAvz($sid,$uid,$wrhFrom,$wrhTo,$avz_params); break; // pedido avanzado
            case 3: $resp = $this->createPrev($sid,$uid,$wrhFrom,$wrhTo,$folio); break; // pedido desde preventa
            case 4: $resp = $this->createFsol($sid,$uid,$wrhFrom,$wrhTo,$folio); break; // pedido desde Factusol
            default: $resp = $this->createBlank($sid,$uid,$wrhFrom,$wrhTo); break; // pedido en blanco
        }

        return response()->json(["resp" => $resp]);
    }

    private function createBlank($sid,$uid,$wrhFrom,$wrhTo){
        $init = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");
        $end = Carbon::now()->endOfDay()->format("Y-m-d H:i:s");
        /**
         * nos: Number Order Store (on day)
         * nod: Number Order Day (general)
         */
        $nos = RestockOrder::where(function($q) use($wrhFrom){ $q->where("warehouse_from",$wrhFrom); })
            ->whereBetween("created_at",[$init,$end])
            ->count();

        $nod = RestockOrder::whereBetween("created_at",[$init,$end])->count();

        $ncs = ($nos+1);
        $ncd = ($nod+1);

        $neworder = new RestockOrder([
            "num_ticket" => $ncd,
            "num_ticket_store" => $ncs,
            "_created_by" => $uid,
            "warehouse_from" => $wrhFrom,
            "warehouse_to" => $wrhTo,
            "_type" => 1,
            "_state" => 1,
            "printed" => 0
        ]);

        $neworder->save();
        $neworder->load([ "owner", "state", "originWrh", "sourceWrh" ]);

        // $log = $this->attachLog();

        return $neworder;
    }

    private function createAvz($sid,$uid,$wrhFrom,$wrhTo,$params){
        $reportType = $params["type"]["id"];
        $sectionsReq = $params["sections"];
        $store = Store::find($sid);
        $seasons = $this->getSeasons($sid);

        $isACedis = ($store->_type == 1);
        $wrhsComps = $this->warehousesCompares($sid,$wrhFrom,$isACedis);
        $warehouse_req = $wrhsComps["warehouse_req"];
        $warehouses_comp = $wrhsComps["warehouses_comp"];
        $ids_wrhs_comp = $warehouses_comp->map( fn($w) => $w->id);

        // obtenemos el reporte de minimos y maximos del almacen (ya comparado contra sus almacenes a surtir)
        $productsToReq = $this->prev_min_max($warehouse_req->id, $ids_wrhs_comp, $seasons["ids"])["basket"];

        // se crea un pedido en blanco para obtener los encabezados
        // $orderRestock = $this->createBlank($sid,$uid,$wrhFrom,$wrhTo);
        // $oid = $orderRestock["id"];
        $oid = 25;

        // se mapean los productos del pedido de minimos y maximos para calcular las reservas y construir el arreglo con las filas a insertar
        // $products = $productsToReq->map(function($p) use($oid){
        //     return [
        //         "_requisition"  => $oid,
        //         "_product"      => $p["_product"],
        //         "comments"      => "",
        //         "stock"         => $p["_current"],
        //         "amount"        => $p["_z_amount_units"],
        //         "cost"          => 0,
        //         "total"         => 0
        //     ];
        // })->toArray();

        try {
            // $restockBody = RestockBody::insert($products);
            $restockBody = true;
            $reserveFromRestock = $this->reserveFromRestock($oid);
        } catch (\Throwable $th) {
            $restockBody = $th->getMessage();
        }

        return [
            "reportType"    => $reportType,
            "seasons"       => $seasons,
            "sectionsReq"   => $sectionsReq,
            "store"         => $store,
            "wrhsComps"     => $wrhsComps,
            // "orderRestock"  => $orderRestock,
            "productsToReq" => $productsToReq,
            "products"      => $products,
            "oid"           => $oid,
            "restockBody"   => $restockBody,
            "reserves"      => $reserveFromRestock
        ];
    }

    private function createPrev($sid,$uid,$wrhFrom,$wrhTo,$folio){
        return ["Se crea pedido desde Preventa", $folio];
    }

    private function createFsol($sid,$uid,$wrhFrom,$folio){
        return ["Se crea pedido desde FactuSol", $folio];
    }

    public function open(Request $request){
        $rid = $request->route('rid');
        $sid = $request->route('sid');
        $uid = $request->fixeds->uid;// usuario que solicita el pedido
        $store = Store::find($sid);
        $isACedis = ($store->_type == 1);

        $order = RestockOrder::findOrFail($rid);
        $order->load([
            "owner",
            "state",
            "originWrh"=>fn($q)=>$q->with("store"),
            "sourceWrh"=>fn($q)=>$q->with("store"),
            "products"=>fn($q)=>$q->with("product")
        ]); // falta incluir el log

        $orgStore = $order->originWrh->store->id; // tienda desde la que se solicito el pedido
        $srcStore = $order->sourceWrh->store->id; // tienda que proveera el resurtido

        $wrhsrc = $order->sourceWrh->id;
        $wrhs = $this->warehousesCompares($sid,$wrhsrc,$isACedis,true);

        if($orgStore==$sid || $srcStore==$sid){
            return response()->json([
                "resid"=>$rid,
                "store"=>$sid,
                "user"=>$uid,
                "order"=>$order,
                "orgstore"=>$orgStore,
                "warehouses"=>$wrhs
            ]);
        }else{ return response("Unauthorized!", 401); }
    }

    public function discard(Request $request){
        $rid = $request->route('rid');
        $sid = $request->route('sid');
        $uid = $request->fixeds->uid;// usuario que ejecuto el request

        $order = RestockOrder::findOrFail($rid);
        $curState = $order->_state;
        $toState = 100;

        $authChange = $this->checkChangeState($curState, $toState);

        if($authChange){
            $unreserve = $this->unreserveFromRestock($rid, $order);
            if($unreserve){
                // $order->_state = 100;
                // $order->save();
                return response()->json([ "unreserve" => $unreserve ]);
            }
        }else{
            return response("No puedes cambiar la orden de $curState a $toState",401);
        }

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
        $wrhsComps = $this->warehousesCompares($sid,$wrhsrc,$isACedis);
        $warehouse_req = $wrhsComps["warehouse_req"];
        $warehouses_comp = $wrhsComps["warehouses_comp"];

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

    public function add(Request $request){

        $rid = $request->route('rid');
        $sid = $request->route('sid');
        $uid = $request->fixeds->uid;// usuario que solicita el pedido
        $idp = $request->product;// id del producto
        $amount = $request->amount;// cantidad del producto
        $unitsupply = $request->unitsupply;// unidad de resurtido

        $order = RestockOrder::find($rid);

        // validar status del pedido

        // validar permisos del usuario sobre el pedido

        // validar que el producto no exista en el pedido

        $resp = [
            "usuario"=>$uid,
            "branch"=>$sid,
            "order"=>$rid,
            "product"=>$idp,
            "amount"=>$amount,
            "order"=>$order
        ];

        return response()->json($resp);
    }

    private function prev_min_max($wrhsrc,$ids_wrhs_comp=[],$seasonsids){

        $stockWarehouse = ProductStock::with(["product"])->where([
            ["_state",1],
            ["_min",">",0],
            ["_max",">",0],
            ["_warehouse",$wrhsrc]
        ])->whereHas("product", function($q) use($seasonsids){
            $q->where("_state",1)->whereIn("_category",$seasonsids);
        })->withSum([
            "stocksProduct" => function($q) use($ids_wrhs_comp){
                return $q->whereIn("_warehouse",$ids_wrhs_comp);
            }],"available")
        ->having('stocks_product_sum_available', '>', 0)
        ->get()
        ->filter(fn($p) => $p["stocks_product_sum_available"] > $p["_min"])
        ->map(function($p){
            $isreq = ($p["available"]<=$p["_max"]);
            $ipack = $p["product"]["pieces"];
            $amount = ($p["_max"]-$p["available"]);

            $p["_z_isreq"] = $isreq;
            $p["_z_amount_units"] = $isreq ? $amount:0;
            $p["_z_amount_packs"] = $isreq ? floor($amount/$ipack) : 0;

            return $p;
        })->values();

        return [
            "wrhFrom"=>$wrhsrc,
            "wrhsTo"=>$ids_wrhs_comp,
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

    private function warehousesCompares($sid,$wrhsrc,$isACedis,$withStore=false){
        $warehouse_req = null;
        $warehouses_comp = null;

        if($isACedis){
            $warehouse_req = Warehouse::find($wrhsrc);
            $warehouses_comp = Warehouse::where([ ["id","!=",$wrhsrc], ["_type",4], ["_state",1], ["_store", $sid] ])->get();
        }else{
            $warehouse_req = Warehouse::where([ ["_type",1], ["_store", $sid] ])->first();
            $warehouses_comp = Warehouse::where([ ["_type",4], ["_state",1], ["_store", 1] ])->get();
        }

        if($withStore){
            $warehouse_req->load("store");
            $warehouses_comp->load("store");
        }

        return [ "warehouse_req"=>$warehouse_req, "warehouses_comp"=>$warehouses_comp ];
    }

    private function unreserveFromRestock($oid, $order){
        $resume = ["fail" => null, "goals" => []];
        $rowsRestock = RestockBody::where([ "_requisition" => $oid ])->select("_product","amount")->get();

        $wrhReq = $order["warehouse_from"]; // almacen SOLICITANTE => aplicar apartados a este almacen (in_come) (tomar el reserved actual y sumar el amount del ticket)
        $wrhSrc = $order["warehouse_to"]; // almacen PROVEEDOR => aplicar reservas a este almacen (tomar
        $idsProd = $rowsRestock->pluck("_product");// obtenemos la lista de ids de productos insertados al requisition

        $wrhsStock = ProductStock::whereIn("_product", $idsProd)
            ->where(function($q) use($wrhReq, $wrhSrc){
                $q->orWhere("_warehouse", $wrhReq )
                ->orWhere("_warehouse", $wrhSrc);
            })->get();

        try {
            DB::beginTransaction();
                foreach ($wrhsStock as $row) {
                    $query = true;
                    $wrh = $row["_warehouse"];
                    $product = $row["_product"];
                    $rowInRestock = $rowsRestock->first(fn($r) => $r["_product"]==$product);
                    $amount = $rowInRestock["amount"];

                    if($wrhSrc == $wrh){
                        $currReserv = $row["reserved"]; // stock reservado actual
                        $newReserv = ($currReserv-$amount); // stock reservado despues de sumar piezas
                        $avlbStock = $row["reserved"];
                        $avlbNew = ($avlbStock+$amount);
                        $query = ProductStock::where([ ["_warehouse",$wrh],["_product", $product] ])->update([ "available"=>$avlbNew, "reserved"=>$newReserv ]); // query que reserva
                        $resume["reserveds"][] = ["qresp"=>$query, "amount"=>$amount, "currReserv"=>$currReserv, "newReserv"=>$newReserv, "product"=>$product, "warehouse"=>$wrh];
                    }elseif($wrhReq == $wrh){
                        $currIncome = $row["in_coming"]; // stock por llegar actual
                        $newIncome = ($currIncome-$amount); // stock reservado despues de sumar piezas
                        $query = ProductStock::where([ ["_warehouse",$wrh],["_product", $product] ])->update(["in_coming"=>$newIncome]); // query que reserva
                        $resume["incomes"][] = ["qresp"=>$query, "amount"=>$amount, "currIncome"=>$currIncome, "newIncome"=>$newIncome, "product"=>$product, "warehouse"=>$wrh];
                    }
                }
            DB::commit();
            return $resume;
        } catch (\Throwable $th) {
            DB::rollBack();
            $resume["fail"] = $th->getMessage();
        }

        return [$rowsRestock, $wrhReq, $wrhsStock, $wrhSrc ];
    }

    private function reserveFromRestock($oid){
        $resume = [ "reserveds" => [], "incomes" => [], "fail"=>null ];
        $query = "fake";
        $head = RestockOrder::find($oid); // encabezado del restock

        $wrhReq = $head["warehouse_from"]; // almacen SOLICITANTE => aplicar apartados a este almacen (in_come) (tomar el reserved actual y sumar el amount del ticket)
        $wrhSrc = $head["warehouse_to"]; // almacen PROVEEDOR => aplicar reservas a este almacen (tomar el stock actual y restarle el amount del ticket)

        // obtenemos el cuerpo del requisition (solo campos _product&amount)
        $rowsRestock = RestockBody::where([ "_requisition" => $oid ])->select("_product","amount")->get();
        $idsProd = $rowsRestock->pluck("_product");// obtenemos la lista de ids de productos insertados al requisition

        // obtenemos el stock de los almacenes en juego (wrhReq&wrhSrc [almacen que solicita, almacen que provee])
        $wrhsStock = ProductStock::whereIn("_product", $idsProd)
            ->where(function($q) use($wrhReq, $wrhSrc){
                $q->orWhere("_warehouse", $wrhReq )
                ->orWhere("_warehouse", $wrhSrc);
            })->get();


        try {
            DB::beginTransaction();
            // iteramos las filas del requisition
            foreach ($wrhsStock as $rowStock) {
                $wrh = $rowStock["_warehouse"];
                $product = $rowStock["_product"];
                $rowInRestock = $rowsRestock->first(fn($r) => $r["_product"]==$product); // obtenemos fila del producto a reservar del el cuerpo del restock (pedido)
                $amount = $rowInRestock["amount"]; // cantidad (en pzs) a reservar, pzs solicitadas en el pedido de resurtido

                if($wrhSrc == $wrh){
                    $currReserv = $rowStock["reserved"]; // stock reservado actual
                    $newReserv = ($currReserv+$amount); // stock reservado despues de sumar piezas
                    $avlbStock = $rowStock["reserved"];
                    $avlbNew = ($avlbStock-$amount);
                    $query = ProductStock::where([ ["_warehouse",$wrh],["_product", $product] ])->update([ "available"=>$avlbNew, "reserved"=>$newReserv ]); // query que reserva
                    $resume["reserveds"][] = ["qresp"=>$query, "amount"=>$amount, "currReserv"=>$currReserv, "newReserv"=>$newReserv, "product"=>$product, "warehouse"=>$wrh];
                }elseif($wrhReq == $wrh){
                    $currIncome = $rowStock["in_coming"]; // stock por llegar actual
                    $newIncome = ($currIncome+$amount); // stock reservado despues de sumar piezas
                    $query = ProductStock::where([ ["_warehouse",$wrh],["_product", $product] ])->update(["in_coming"=>$newIncome]); // query que reserva
                    $resume["incomes"][] = ["qresp"=>$query, "amount"=>$amount, "currIncome"=>$currIncome, "newIncome"=>$newIncome, "product"=>$product, "warehouse"=>$wrh];
                }
            }

            // DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            $resume["fail"] = $th->getMessage();
        }

        return [
            "wrhReq"        => $wrhReq,
            "wrhSrc"        => $wrhSrc,
            "resume"        => $resume,
            "currStock"     => $wrhsStock
        ];
    }

    private function checkChangeState($from, $to, $onlyOwner=false){
        $change = false;

        $avlChanges = [
            "1" => [2,3,100],
            "2" => [3,100]
        ];

        $change = array_search($to, $avlChanges[$from]);

        return $change;
    }

    private function attachLog($restock, $state){

    }
}

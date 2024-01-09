<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransferBW;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\TransferBWUsers;
use App\Models\TransferBWProduct;
use Carbon\Carbon;

class AppTransfers extends Controller
{
    public function index(Request $request){
        /**
         * Obtener la fecha del dia en transito
         * Buscar un traspaso abierto correspondiente a la tienda y fecha del dia en transito
         */
        $store = $request->route('sid');
        $init = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");
        $end = Carbon::now()->endOfDay()->format("Y-m-d H:i:s");

        $transfers = TransferBW::with([
                    "from",
                    "to",
                    "created_by",
                    "state",
                    "transferists" => fn($q) => $q->with("account")
                ])
                ->withCount("basket")
                ->whereHas('from.Store', function($q) use($store){ $q->where('id',$store); })
                ->whereBetween('created_at',[$init,$end])
                ->get();

        return response()->json(["transfers"=>$transfers]);
    }

    public function create(Request $request){
        $creator = $request->fixeds->uid;
        $wh1 = $request->wh1;
        $wh2 = $request->wh2;
        $transferists = $request->transferists;
        $bidir = $request->bidir;
        $transfer2 = null;

        $transfer = new TransferBW();
        $transfer->_origin_warehouse = $wh1;
        $transfer->_destini_warehouse = $wh2;
        $transfer->_user = $creator;
        $transfer->_state = 1;
        $transfer->save();

        $id = $transfer->id;
        $users = collect($transferists)->map( fn($t) => ["_transfer"=>$id,"_user"=>$t])->all();
        TransferBWUsers::insert($users);

        $transfer->load(["from", "to", "created_by", "state", "transferists" => fn($q) => $q->with("account")]);

        if($bidir){
            $transfer2 = new TransferBW();
            $transfer2->_origin_warehouse = $wh2;
            $transfer2->_destini_warehouse = $wh1;
            $transfer2->_user = $creator;
            $transfer2->_state = 1;
            $transfer2->save();

            $id = $transfer2->id;
            $users = collect($transferists)->map( fn($t) => ["_transfer"=>$id,"_user"=>$t])->all();
            TransferBWUsers::insert($users);

            $transfer2->load(["from", "to", "created_by", "state", "transferists" => fn($q) => $q->with("account")]);
        }

        return response()->json(["tr1"=>$transfer, "tr2"=>$transfer2]);
    }

    public function adminView(Request $request){
        /**
         * Este metodo retorna los datos necesarios para poder crear traspasos
         */
        $store = $request->route('sid');
        $fixeds = $request->fixeds;

        $warehouses = Warehouse::whereIn('_store',[$store])->get();
        $users = User::whereIn('_store', [$store])
            ->with(["rol"])
            ->get();

        return response()->json(["warehouses"=>$warehouses, "users"=>$users]);
    }

    public function open(Request $request){
        $store = $request->route('sid');
        $tid = $request->route('tid');
        $uid = $request->fixeds->uid;
        $mode = "r";
        $today = Carbon::now()->startOfDay()->format("Y-m-d H:i:s");

        $transfer = TransferBW::find($tid);
        if($transfer){
            $transfer->load(["from",
                                "to",
                                "created_by",
                                "state",
                                "transferists" => fn($q) => $q->with("account"),
                                "basket" => fn($q) => $q->with(["product", "addby"])
                            ]);
            $data = [
                "transfer"=>$transfer,
                "store"=>$store,
                "tid"=>$tid,
                "user"=>$uid,
                "mode"=>$mode
            ];
            return response()->json($data);
        }

        return response("Not Found", 404);
    }

    public function push(Request $request){
        $store = $request->route('sid');
        $tid = $request->route('tid'); // id del traspaso
        $uid = $request->fixeds->uid; // id del usuario
        $product = $request->product; // id del produco
        $amount = $request->amount; //unidades a traspasar

        $transfer = TransferBW::find($tid); // datos del traspaso

        $wrhFrom = $transfer->_origin_warehouse; // id del almacen de origen
        $wrhTo = $transfer->_destini_warehouse; // id del almacen destino

        /**
         * Validar status del traspaso
         * Validar el dia en transito contra el dia de creacion del traspaso
         * Validar que el usuario tenga acceso al traspaso
         * (SOLICITAR a HUGO la creacion de campos timestamps en el bodi del traspaso)
         * Insertar registro
         */
        $conditions = [
            ["_transfer",$tid],
            ["_product",$product],
            ["_user",$uid],
        ];

        $row = TransferBWProduct::where($conditions)->first();

        if($row){
            $row->amount = $amount;

            TransferBWProduct::where($conditions)->update(["amount"=>$amount]);

            return response()->json([ "action"=>"update", "row"=>$row, "conditions"=>$conditions]);
        }else{
            $row = new TransferBWProduct();
            $row->_transfer = $tid;
            $row->_product = $product;
            $row->amount = $amount;
            $row->cost = 1;
            $row->_user = $uid;
            $row->save();

            $row->load(["product", "addby"]);
            return response()->json([ "action"=>"create", "row"=>$row, "conditions"=>$conditions]);
        }
    }
}

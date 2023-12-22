<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransferBW;
use App\Models\Warehouse;
use App\Models\User;
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

        $transfers = TransferBW::with([ "from", "to", "created_by", "state" ])
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

        $transfer->load(["from", "to", "created_by", "state"]);

        if($bidir){
            $transfer2 = new TransferBW();
            $transfer2->_origin_warehouse = $wh2;
            $transfer2->_destini_warehouse = $wh1;
            $transfer2->_user = $creator;
            $transfer2->_state = 1;
            $transfer2->save();

            $transfer2->load(["from", "to", "created_by", "state"]);
        }

        return response()->json(["tr1"=>$transfer,"tr2"=>$transfer2]);
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
}

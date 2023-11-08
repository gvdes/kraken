<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\RestockOrder;
use Illuminate\Http\Request;
use App\Models\RestockStates;
use App\Models\Warehouse;
use App\Models\Store;
use App\Models\Product;
use Carbon\Carbon;

class RestockController extends Controller
{
    public function index(Request $request){
        $sid = $request->route('sid');
        $_init = $request->query('init');
        $_end = $request->query('end');

        $init = Carbon::parse($_init)->startOfDay()->format("Y-m-d H:i:s");
        $end = Carbon::parse($_end)->endOfDay()->format("Y-m-d H:i:s");

        $states = RestockStates::all();
        $stores = Store::where("_type",1)->get();

        $orders = RestockOrder::with([ "owner", "state", "fromStore", "toStore" ])
                    ->where(function($q) use($sid){ $q->where("_store_from",$sid)->orWhere("_store_to",$sid); })
                    ->whereBetween("created_at",[$init,$end])
                    ->get();

        return response()->json([
            "stores"=>$stores,
            "states" => $states,
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

        $wrh = Warehouse::where([ ["_store",$sid],["_type",1] ])->first();
        $wid = $wrh->id;

        $products = Product::whereHas("stocks", function($q) use($wid){
            $q->where([ ["_state",1], ["_warehouse",$wid] ])->whereRaw("((_current<_max) and (_min>0 and _max>0))");
        })->get();

        return response()->json([ "productsdb"=>$products ]);
    }
}

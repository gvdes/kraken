<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLog;


class PreorderController extends Controller
{
    public function index(Request $request){
        $store = $request->route('sid');
        $clients = Client::where([['_type',2],['_state',1]])->get();
        $res = [
            "sid"=>$store,
            "clients"=>$clients
        ];
        return response()->json($res,200);
    }

    public function getOrder(Request $request){
        $id = $request->route('oid');
        $store = $request->route('sid');
        $order = Order::where([['id',$id],['_store',$store]])->first();
        if($order){
            return response()->json($order,200);
        }else{
            return response()->json("No se encontro el pedido $id",404);
        }
    }

    public function createOrder(Request $request){
        $store = $request->route('sid');
        $user = $request->fixeds->uid;
        $order = Order::max('id') + 1;
        $tck = Order::where('_store',$store)->whereDate('created_at',now())->max('num_ticket') + 1;
        $status = 1;
        $typelog = 1;
        $client = $request->_client;
        $name = $request->name;
        $log = [
            "details"=>json_encode([
                "name"=>$name,
                "client"=>$client,
                "store"=>$store
            ]),
            "_state"=>$status,
            "_order"=>$order,
            "_user"=>$user,
            "_type"=>$typelog
        ];
        $listor = [
            "id"=>$order,
            "_client"=>$client,
            "name"=>$name,
            "num_ticket"=>$tck,
            "time_life"=>'00:15:00',
            "_created_by"=>$user,
            "_state"=>$status,
            "_store"=>$store,
        ];
        $insOr = Order::insert($listor);
        if($insOr){
            $savelog = $this->logs($log);
            if($savelog){
                $norder = Order::find($order);
                return response()->json($norder);
            }else{
                return response()->json("No se Genero el log",500);
            }
        }else{
            return response()->json("No se pudo crear el pedido bro",500);
        }

    }

    public function logs($log){
        $max = OrderLog::max('id') + 1;
        $log['id'] = $max;
        $inslog = OrderLog::insert($log);
        $res = $inslog ? true : false;
        return $res;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\Store;
use App\Models\UnitMeassure;
use App\Models\Warehouse;
use App\Models\OrderLog;
use App\Models\OrderBodie;
use Carbon\Carbon;


class PreorderController extends Controller
{
    public function index(Request $request){
        $store = $request->route('sid');
        $user =  $request->fixeds->uid;
        $preorders = Order::with('user','state')->where([['_store',$store],['_created_by',$user]])->whereDate('created_at',now())->get();
        $clients = Client::where([['_type',2],['_state',1]])->get();
        $res = [
            "sid"=>$store,
            "preorders"=>$preorders,
            "clients"=>$clients
        ];
        return response()->json($res,200);
    }

    public function getOrders(Request $request){
        $to =  $request->to;
        $from =  $request->from;
        $store = $request->route('sid');
        $preorders = Order::with('user','state')->where('_store', $store)->whereDate('created_at','>=',$from)->whereDate('created_at','<=',$to)->get();
        return response()->json($preorders,200);
    }

    public function getOrder(Request $request){
        $id = $request->route('oid');
        $store = $request->route('sid');
        $suc = Store::find($store); // obtiene la sucursal

        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );

        $order = Order::with([
            'bodie.product.stocks' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs),
            'bodie.product.prices' => fn($q) => $q->with(['rates'])->where('_type',$suc->_price_type),
            'client.rate',
            'bodie.product.measure',
            'bodie.product.category.familia.seccion',
            'bodie.unitsupply',
            'bodie.rates'])->where([['id',$id],['_store',$store]])->first();
        $units = UnitMeassure::all();
        if($order){
            $res = [
                "unit_measures"=>$units,
                "order"=>$order
            ];
            return response()->json($res,200);
        }else{
            return response()->json("No se encontro el pedido $id",404);
        }
    }
    public function getOrderforuser(Request $request){
        $store = $request->route('sid');
        $user = $request->fixeds->uid;
        $orders = Order::where([['_store',$store],['_created_by',$user]])->whereDate('created_at',now())->get();
        return response()->json($orders,200);
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
                $norder = Order::with('user','state')->where('id',$order)->first();
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

    public function addProduct(Request $request){
        $order = OrderBodie::create($request->all());
        $store = $request->route('sid');
        $suc = Store::find($store); // obtiene la sucursal
        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );
        if($order){
            $bodie = OrderBodie::with([
                'product.stocks' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs),
                'product.prices' => fn($q) => $q->with(['rates'])->where('_type',$suc->_price_type),
                'product.measure',
                'product.category.familia.seccion',
                'unitsupply',
                'rates'])->where([['_product',$request->_product],['_order',$request->_order]])->first();
            return $bodie;
        }else{
            return response()->json('No se pudo agregar el producto',401);
        }
    }

    public function removeProduct(Request $request){
        $bodie = OrderBodie::where([['_product',$request->_product],['_order',$request->_order]])->delete();
        if($bodie){
            return response()->json($bodie,200);
        }else{
            return response()->json('Se ocaciono un problema al eliminar el articulo',500);
        }
    }
}

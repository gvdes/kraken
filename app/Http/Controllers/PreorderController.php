<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\Store;
use App\Models\UnitMeassure;
use App\Models\Warehouse;
use App\Models\OrderLog;
use App\Models\Product;
use App\Models\OrderBodie;
use App\Models\Seasons;
use App\Models\SeassonBussinesRules;
use App\Models\Printer;
use App\Models\OrderStateConfig;
use App\Models\CashRegister;
use Carbon\Carbon;



class PreorderController extends Controller
{
    public function index(Request $request){
        $store = $request->route('sid');
        $user =  $request->fixeds->uid;
        $preorders = Order::with('user','state','order')->where([['_store',$store],['_created_by',$user]])->whereDate('created_at',now())->get();
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
        $preorders = Order::with('user','state','order','cash')->where('_store', $store)->whereDate('created_at','>=',$from)->whereDate('created_at','<=',$to)->get();
        return response()->json($preorders,200);
    }

    public function getOrdersCheckin(Request $request){
        $store = $request->route('sid');
        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );
        $store = $request->route('sid');
        $user =  $request->fixeds->uid;
        $preorders = Order::with(['user',
        'state',
        'order',
        'cash',
        'bodie.product.stocks' => fn($q) => $q->whereHas('warehouse', fn($q) => $q->where('_type', 1))
            ->with('warehouse')->whereIn("_warehouse", $onWrhs),
        'bodie.product.locations' => fn($q) => $q->whereHas('warehouse', fn($q) => $q->where('_type', ))
            ->with("warehouse")->whereIn("_warehouse", $onWrhs)
        ])->where([['_store',$store]])->whereDate('created_at',now())->get();
        $clients = Client::where([['_type',2],['_state',1]])->get();
        $res = [
            "sid"=>$store,
            "preorders"=>$preorders,
            "clients"=>$clients
        ];
        return response()->json($res,200);
    }

    public function getOrder(Request $request){
        $id = $request->route('oid');
        $store = $request->route('sid');
        $suc = Store::find($store); // obtiene la sucursal
        // $user = $request->fixeds->uid;

        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );

        $order = Order::with([
            'products.category.familia.seccion',
            'products.measure',
            'products.stocks' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs),
            'products.prices' => fn($q) => $q->with(['rates'])->where('_type',$suc->_price_type),
            'user',
            'client.rate',
            ])->where([['id',$id],['_store',$store]])->first();
        $order->products->each(function ($product) {$product->pivot->load(['unitsupply', 'rates']);});
        $units = UnitMeassure::all();
        $rules = Seasons::with('rules')->get();
        if($order){
            $res = [
                "unit_measures"=>$units,
                "order"=>$order,
                "rules"=>$rules
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

    public function getConfig(Request $request){
        $store = $request->route('sid');
        $configs = OrderStateConfig::with('state')->where('_store',$store)->get();
        if($configs){
            return response()->json($configs);
        }else{
            return response()->json('No hay configuraciones',500);
        }
    }

    public function changeConfig(Request $request){
        $config = OrderStateConfig::where([['_state_order',$request->_state_order],['_store',$request->route('sid')]])
        ->update(['active'=>$request->active]);
        if($config == 1){
            return response()->json($request->all());
        }else{
            return response()->json('No se pudo cambiar el status',401);
        }
    }

    public function createOrder(Request $request){
        $ip = $request->ip();
        $store = $request->route('sid');
        $user = $request->fixeds->uid;
        $order = Order::max('id') + 1;
        $tck = Order::where('_store',$store)->whereDate('created_at',now())->max('num_ticket') + 1;
        $status = 1;
        $typelog = 1;
        $client = $request->_client;
        $name = $request->name;
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
            $norder = Order::with('user','state','order')->where('id',$order)->first();
            $savelog = $this->createLog($status, $typelog, $norder,$ip);
            if($savelog['log']){
                return response()->json($norder);
            }else{
                return response()->json($savelog['message'],500);
            }
        }else{
            return response()->json("No se pudo crear el pedido bro",500);
        }
    }

    public function createOrderAnexo(Request $request){
        $ip = $request->ip();
        $anex = $request->id;
        $store = $request->route('sid');
        $user = $request->fixeds->uid;
        $order = Order::max('id') + 1;
        $tck = Order::where('_store',$store)->whereDate('created_at',now())->max('num_ticket') + 1;
        $status = 1;
        $typelog = 1;
        $client = $request->_client;
        $name = $request->name;
        $listor = [
            "id"=>$order,
            "_client"=>$client,
            "name"=>$name,
            "num_ticket"=>$tck,
            "time_life"=>'00:15:00',
            "_created_by"=>$user,
            "_state"=>$status,
            "_store"=>$store,
            "_order_by"=>$anex
        ];
        $insOr = Order::insert($listor);
        if($insOr){
            $norder = Order::with('user','state','order.bodie')->where('id',$order)->first();
            $savelog = $this->createLog($status, $typelog, $norder,$ip);
            if($savelog['log']){
                return response()->json($norder);
            }else{
                return response()->json($savelog['message'],500);
            }
        }else{
            return response()->json("No se pudo crear el pedido bro",500);
        }
    }

    public function addProduct(Request $request){
        $bodie = $request->all();
        unset($bodie['supply_by']);
        $order = OrderBodie::create($bodie);
        $store = $request->route('sid');
        $suc = Store::find($store);
        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );
        if($order){
            $product = Product::with([
                'stocks' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs),
                'prices' => fn($q) => $q->with(['rates'])->where('_type',$suc->_price_type),
                'measure',
                'category.familia.seccion',
                'pivot' => fn($q) =>  $q->where('_order',$bodie['_order']),
                'pivot.unitsupply', 'pivot.rates'
            ])->where('id',$bodie['_product'])->first();
            return $product;
        }else{
            return response()->json('No se pudo agregar el producto',401);
        }
    }

    public function ModifyProduct(Request $request){
        $order = OrderBodie::where([['_order',$request->_order],['_product',$request->_product]])
        ->update([
            'amount_require' => $request->amount_require,
            'notes' => $request->notes,
            'price' => $request->price,
            'total' => $request->total,
            'units' => $request->units,
            '_rate' => $request->_rate,
            '_state' => $request->_state,
            '_supply_by' => $request->_supply_by
        ]);

        $store = $request->route('sid');
        $suc = Store::find($store); // obtiene la sucursal
        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );
        // if($order > 0){
            $bodie = OrderBodie::with([
                'product.stocks' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs),
                'product.prices' => fn($q) => $q->with(['rates'])->where('_type',$suc->_price_type),
                'product.measure',
                'product.category.familia.seccion',
                'unitsupply',
                'rates'])->where([['_product',$request->_product],['_order',$request->_order]])->first();
            return $bodie;
        // }else{
            // return response()->json('El Producto no necesito de modificacion',200);
        // }
    }

    public function removeProduct(Request $request){
        $bodie = OrderBodie::where([['_product',$request->_product],['_order',$request->_order]])->delete();
        if($bodie){
            return response()->json($bodie,200);
        }else{
            return response()->json('Se ocaciono un problema al eliminar el articulo',500);
        }
    }

    public function changeStatus(Request $request){
        $uid = $request->fixeds;
        $store = $request->route('sid');
        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );
        $order = Order::with([
            'store',
            'user',
            'state',
            'bodie.product.category.familia.seccion',
            'bodie.rates',
            'cash' => fn($q) => $q->with(['cashier.printer_order'])->max('created_at'),
            'bodie.product.stocks' => fn($q) => $q->whereHas('warehouse', fn($q) => $q->where('_type', 1))
                ->with('warehouse')->whereIn("_warehouse", $onWrhs),
            'bodie.product.locations' => fn($q) => $q->whereHas('warehouse', fn($q) => $q->where('_type', 1))
                ->with("warehouse")->whereIn("_warehouse", $onWrhs)
        ])->where('id',$request->id)->first();
        $status = $request->_state + 1;
        $printer = isset($request->printer) ? $request->printer : null ;
        $typelog = 7;
        $ip = $request->ip();
        $create_log = $this->createLog($status, $typelog, $order, $ip, $printer,$uid->uid);
        if($create_log['log']){
            $order->_state = $create_log['status'];
            $order->save();
            $res =$order->fresh(['store',
            'user',
            'state',
            'bodie.product.category.familia.seccion',
            'bodie.rates',
            'cash']);

            return response()->json($res);
        }else{
            return response()->json($create_log['message'],400);
        }
    }

    public function getPrints(Request $request){
        $store = $request->route('sid');
        $type = $request->route('type');
        $printers = Printer::where([['_store',$store],['_type',$type]])->get();
        return response()->json($printers,200);
    }

    public function createLog($_status, $typelog,$order, $ip, $print = null,$requestUs = null){
        $store = $order->_store;
        $user = $order->_created_by;
        $status = $_status;
        $client = $order->_client;
        $name = $order->name;
        $uid = $requestUs;

        $log = [
            "details"=>json_encode([
                "name"=>$name,
                "client"=>$client,
                "store"=>$store,
                "ip_device"=>$ip
            ]),
            "_state"=>$status,
            "_order"=>$order->id,
            "_user"=>$user,
            "_type"=>$typelog
        ];

        $create_log = null;
        switch($status){
            case 1://levantando pedido
                $create_log= $this->logs($log);
                $islog = $create_log;
                $message = 'Log Creado';
            break;
            case 2://Recepcion
                // se refiere a los pedidos que salen solo en la impresora de preventa para que el cliente lo valide con el valildador ?
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);
                    $printer = Printer::find($print);
                    if($order->_order_by){
                        $cash = $order->order['_cash'];
                    } else {
                        $selectecCash = $this->selectCash($store);
                        if($selectecCash['message']){
                            $cash = $selectecCash['cash'];
                        }else{
                            $islog = false;
                            $message = "No hay cajas abiertas";
                            break;
                        }
                    }

                    $cashier = CashRegister::find($cash);
                    $order = Order::find($order->id);
                    $order->_cash = $cashier->id;
                    $order->save();
                    $cellerPrinter = new MiniPrinterController($printer->ip_address, $printer->_port,5);
                    $res =  $cellerPrinter->CliOrder($order,$status,$cashier);
                    if($res){
                    }else{
                        $islog = false;
                        $message = "No se logro imprimir";
                        break;
                    }
                    $islog = $create_log;
                    $message = "Status cambiado";
                    break;
                }else{
                    $status = 3;
                    $log['_state'] = 3;
                }
            case 3://Por Surtir
                //se refiere a los pedidos que salen directos en el almacen para ser repartidos entre los almacenistas
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);//se genera el log de el pedido
                    if(is_null($order['_cash'])){
                        $printer = Printer::find($print);
                        if($order->_order_by){
                            $cash = $order->order['_cash'];
                        } else {
                            $selectecCash = $this->selectCash($store);
                            if($selectecCash['message']){
                                $cash = $selectecCash['cash'];
                            }else{
                                $islog = false;
                                $message = "No hay cajas abiertas";
                                break;
                            }
                        }
                        $cashier = CashRegister::find($cash);
                        $order = Order::find($order->id);
                        $order->_cash = $cashier->id;
                        $order->save();
                        $order->fresh(['cash' => fn($q) => $q->with(['cashier.printer_order'])->max('created_at')]);
                        $cellerPrinter = new MiniPrinterController($printer->ip_address, $printer->_port,5);
                        $res = $cellerPrinter->CliOrder($order,$status,$cashier);
                        if($res){
                            $order->increment('printer');
                            $order->save();
                        }else{
                            $islog = false;
                            $message = "No se logro imprimir";
                            break;
                        }
                    }

                    $printer = Printer::find($order['cash']['cashier']['_printer_order']);// se obtinene la impresora por la que saldran los pedidos de la caja seleccionada
                    $cashier = CashRegister::find($order['cash']['id']);// se obtinene la caja
                    $cellerPrinter = new MiniPrinterController($printer->ip_address, $printer->_port,5);
                    $res = $cellerPrinter->orderReceip($order,$status,$cashier);
                    if($res){
                        $order->increment('printer');
                        $order->save();
                    }else{
                        $islog = false;
                        $message = "No se logro imprimir";
                        break;
                    }
                    $islog = $create_log;
                    $message = "Status cambiado";

                    break;
                }else{
                    $status = 4;
                    $log['_state'] = 4;
                }
            case 4://surtiendo//aqui si se debe de revisar que impresora de almacen va a imprimir dependiendo de la caja que tenga
                //se debe de poner en los productos quien esta surtiendo el pedido
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);//se genera el log de el pedido
                    if(is_null($order['_cash'])){
                        $printer = Printer::find($print);
                        if($order->_order_by){
                            $cash = $order->order['_cash'];
                        } else {
                            $selectecCash = $this->selectCash($store);
                            if($selectecCash['message']){
                                $cash = $selectecCash['cash'];
                            }else{
                                $islog = false;
                                $message = "No hay cajas abiertas";
                                break;
                            }
                        }
                        $cashier = CashRegister::find($cash);
                        $order = Order::find($order->id);
                        $order->_cash = $cashier->id;
                        $order->save();
                        $order->fresh(['cash' => fn($q) => $q->with(['cashier.printer_order'])->max('created_at')]);
                        $cellerPrinter = new MiniPrinterController($printer->ip_address, $printer->_port,5);
                        $res = $cellerPrinter->CliOrder($order,$status,$cashier);
                        if($res){
                            $order->increment('printer');
                            $order->save();
                        }else{
                            $islog = false;
                            $message = "No se logro imprimir";
                            break;
                        }
                    }
                    if($order->pinter > 0){
                        $printer = Printer::find($order['cash']['cashier']['_printer_order']);// se obtinene la impresora por la que saldran los pedidos de la caja seleccionada
                        $cashier = CashRegister::find($order['cash']['id']);// se obtinene la caja
                        $cellerPrinter = new MiniPrinterController($printer->ip_address, $printer->_port,5);
                        $res = $cellerPrinter->orderReceip($order,$status,$cashier);
                        if($res){
                            $order->increment('printer');
                            $order->save();
                        }else{
                            $islog = false;
                            $message = "No se logrdo imprimir";
                            break;
                        }
                    }
                    $islog = $create_log;
                    $message = "Status cambiado";
                    $orderBodie = OrderBodie::where('_order',$order->id)->update(['_assorted_by'=>$uid]);
                    break;
                }else{
                    $islog = false;
                    $message = "No esta activo el proceso";
                    break;
                }
            case 5:// por validar
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);//se genera el log de el pedido
                    $islog = $create_log;
                    $message = "Status cambiado";
                    break;
                }else{
                    $islog = false;
                    $message = "No esta activo el proceso";
                    break;
                }
            case 6://checkout
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);//se genera el log de el pedido
                    $islog = $create_log;
                    $message = "Status cambiado";
                    break;
                }else{
                    $islog = false;
                    $message = "No esta activo el proceso";
                    break;
                }
            case 7://por cobrar
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);//se genera el log de el pedido
                    $islog = $create_log;
                    $message = "Status cambiado";
                    break;
                }else{
                    $islog = false;
                    $message = "No esta activo el proceso";
                    break;
                }
            case 8:
                $validate = $this->verifyProcess($status,$store);
                if($validate){
                    $create_log= $this->logs($log);//se genera el log de el pedido
                    $islog = $create_log;
                    $message = "Status cambiado";
                    break;
                }else{
                    $islog = false;
                    $message = "No esta activo el proceso";
                    break;
                }
            case 9:
            break;
        }
        $res = [
            "log"=>$islog,
            "message"=>$message,
            "status"=>$status
        ];

        return $res;
    }

    public function logs($log){
        $max = OrderLog::max('id') + 1;
        $log['id'] = $max;
        $inslog = OrderLog::insert($log);
        $res = $inslog ? true : false;
        return $res;
    }

    public function verifyProcess($_status,$_store){
        $process = OrderStateConfig::where([['_state_order',$_status],['_store',$_store]])->first();
        if($process->active == 1){
            return true;
        }else{
            return false;
        }
    }

    public function selectCash($store){
        $cashs = CashRegister::where([['_store',$store],['_state',1]])->get();
        if(count($cashs) > 0){
            $cashi = [];
            foreach($cashs as $cash){
                $order = Order::whereDate('created_at',date('Y-m-d'))->where([['_cash',$cash->id],['_state','<=',5]])->count();
                $cashi[$cash->id] = $order;
            }
            $valmin =  min($cashi);
            $mininx = array_search($valmin, $cashi);
            $res = [
                "message"=>true,
                "cash"=>$mininx
            ];
        }else{
            $res=[
                "message"=>false,
                "cash"=>0
            ];
        }
        return $res;
    }

    public function reprintOrderWarehouse(Request $request){
        $uid = $request->fixeds;
        $store = $request->route('sid');
        $onWrhs = $request->query('warehouses') ?
        explode(",",$request->query('warehouses')) :
        Warehouse::select("id")->where("_store",$store)->get()->map( fn($r) => $r->id );
        $order = Order::with([
            'store',
            'user',
            'state',
            'bodie.product.category.familia.seccion',
            'bodie.rates',
            'cash' => fn($q) => $q->with(['cashier.printer_order'])->max('created_at'),
            'bodie.product.stocks' => fn($q) => $q->whereHas('warehouse', fn($q) => $q->where('_type', 1))
                ->with('warehouse')->whereIn("_warehouse", $onWrhs),
            'bodie.product.locations' => fn($q) => $q->whereHas('warehouse', fn($q) => $q->where('_type', 1))
                ->with("warehouse")->whereIn("_warehouse", $onWrhs)
        ])->where('id',$request->id)->first();
        $printer = Printer::find($request->print);
        $status = $order->_state;
        $cashier = $order->cash;

        $cellerPrinter = new MiniPrinterController($printer->ip_address, $printer->_port,5);
        $res = $cellerPrinter->orderReceip($order,$status,$cashier);
        if($res){
            $order->increment('printer');
            $order->save();
            $message = 'Fue Impresa Correctamente';
            return response()->json($message,200);
        }else{
            $message = 'La impresora no tienen conexion';
            return response()->json($message,500);
        }

    }

    public function preorder(Request $request){

    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashRegister;
use App\Models\CashState;
use App\Models\User;
use App\Models\Printer;
use App\Models\Store;
use App\Models\CashCashier;
use App\Models\CashAutomate;
use App\Models\CashLog;
use Illuminate\Support\Facades\Http;




class CashController extends Controller
{
    public function getCash(Request $request){
        $store = $request->route('sid');
        $cashier = USER::where('_store',$store)->whereIn('_rol',[13,14])->get();

        $cash = CashRegister::with([
            'state',
            'cashier' => fn($q) => $q->with('user','printer')->whereDate("created_at", date('Y-m-d'))
            ])->where('_store',$store)->get();
        $states = CashState::get();
        $printers = Printer::where([['_store',$store],['_type',1]])->get();
        $cashIds = $cash->pluck('id')->toArray();
        $automate = CashAutomate::with('cash','user','printer')->whereIn('_cash',$cashIds)->get();
        $res = [
            "cashier"=>$cashier,
            "cash"=>$cash,
            "state"=>$states,
            "printers"=>$printers,
            "automate"=>$automate,
            "date"=>date('Y-m-d'),
        ];
        return response()->json($res,200);
    }

    public function OpenCash(Request $request){
        $res = [];
        $store = $request->route('sid');
        $ipstore = Store::find($store);
        $cashier = $request->all();
        $cash = CashRegister::find($cashier['_cash']);
        if($cash){
        try{
            $url = $ipstore->local_domain.':'.$ipstore->local_port.'/Addicted/public/api/cash/OpenCash';
            $open = Http::post($url,$cashier);
            if($open->status() == 200){
               $cashier['created_at'] =  $open['fechas'] ;
               $cashier['id_tpv'] = $open['idtpv'];
               $cashier['start_time']= date('H:i:s',strtotime($open['fechas']));
               $cashieradd = new CashCashier();
               $cashieradd->_cashier = $cashier['_cashier']['id'];
               $cashieradd->_cash = $cashier['_cash'];
               $cashieradd->_printer = $cashier['_printer']['id'];
               $cashieradd->created_at = $cashier['created_at'];
               $cashieradd->id_tpv = $cashier['id_tpv'];
               $cashieradd->initial_cash = $cashier['initial_cash'];
               $cashieradd->start_time = $cashier['start_time'];
               $cashieradd->save();
               $res = $cashieradd->fresh()->toArray();
               if($res){
                $cash->_state = 1;
                $cash->save();
                $response = $cash->load([
                    'state',
                    'cashier' => fn($q) => $q->with('user','printer')->whereDate("created_at", date('Y-m-d'))
                ]);
                $maxlog = CashLog::max('id');
                $log = [
                    "id"=>$maxlog ? $maxlog : 1,
                    "_cash"=>$cash->id,
                    "_state"=>$cash->_state,
                    "details"=>json_encode([
                        "ip"=>$request->ip(),
                    ]),
                    "_user"=>$request->fixeds->uid,
                    "_type"=>9
                ];
                $inslog = CashLog::insert($log);
                if($inslog){
                    return  response()->json($response,200);
                }
               }else{
                return response()->json('No se creo la apertura de caja',500);
               }
            }else{

            }
        }catch (\Illuminate\Http\Client\ConnectionException $e){
            $res['ping'] = $e;
        }

            return response()->json($res);
        }else{
            return response()->json('No existe la caja',404);
        }
    }

    public function automateCash(){}
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashRegister;
use App\Models\CashState;
use App\Models\User;
use App\Models\Printer;
use App\Models\Store;
use App\Models\Client;
use App\Models\Warehouse;
use App\Models\CashCashier;
use App\Models\CashAutomate;
use App\Models\DocumentType;
use App\Models\TPV;
use App\Models\CashLog;
use Illuminate\Support\Facades\Http;




class CashController extends Controller
{

    public function Index(){
        $prints = Store::with(['cash.state','cash.tpv','cash.document'])->get();
        $state = CashState::all();
        $documents = DocumentType::all();
        $tpv = TPV::all();
        $res = [
            "stores"=>$prints,
            "state"=>$state,
            "documents"=>$documents,
            "tpv"=>$tpv
        ];
        return response()->json($res);
    }

    public function getCash(Request $request){
        $store = $request->route('sid');
        $cashier = USER::where('_store',$store)->whereNotIn('_state',[3,4])->get();// todos los usuarios de la sucursal que esten disponibles

        $cash = CashRegister::with([
            'state',
            'document',
            'tpv',
            'cashier' => fn($q) => $q->with('user','printer','printer_order')->max('created_at')
            ])->where([['_store',$store]])->get();

        $states = CashState::get();
        $printers = Printer::where([['_store',$store]])->get();
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
            $url = $ipstore->local_domain.':'.$ipstore->local_port.'/addicted/public/api/cash/OpenCash';
            $open = Http::post($url,$cashier);
            if($open->status() == 200){
               $cashier['created_at'] =  $open['fechas'] ;
               $cashier['id_tpv'] = $open['idtpv'];
               $cashier['start_time']= date('H:i:s',strtotime($open['fechas']));
               $cashieradd = new CashCashier();
               $cashieradd->_cashier = $cashier['_cashier']['id'];
               $cashieradd->_cash = $cashier['_cash'];
               $cashieradd->_printer_tck = $cashier['_printer_tck']['id'];
               $cashieradd->_printer_order = $cashier['_printer_order']['id'];
               $cashieradd->created_at = $cashier['created_at'];
               $cashieradd->id_tpv = $cashier['id_tpv'];
               $cashieradd->initial_cash = $cashier['initial_cash'];
               $cashieradd->number_closures = 0;
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
                    "id"=>$maxlog ? $maxlog + 1 : 1,
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
                return response()->json($open,500);
            }
        }catch (\Illuminate\Http\Client\ConnectionException $e){
            $res['ping'] = $e;
        }
            return response()->json($res);
        }else{
            return response()->json('No existe la caja',404);
        }
    }

    public function closeBox(Request $request){
        // return $request->all();
        $store = $request->route('sid');
        $ipstore = Store::find($store);
        $closcash  = $request->close;
        $reqcash = $request->cash;
        $idcash = $reqcash['id'];
        $date = $reqcash['cashier']['created_at'];
        $idtpv = $reqcash['cashier']['id_tpv'];

        $url = $ipstore->local_domain.':'.$ipstore->local_port.'/addicted/public/api/cash/CloseCash';
        $close = Http::post($url,$request->all());
        if($close->status() == 200){
            $cash = CashRegister::find($idcash);
            $cash->_state = 2;
            $cash->save();
            $cash->fresh();

            if($cash){
                $upd = [
                    'final_cash' => $closcash['total'],
                    'number_closures' => 1,
                    'end_time' => date('H:i:s'),
                    'details' => json_encode(['Monedas'=>$closcash['Monedas'],'Billetes'=>$closcash['Billetes']])
                ];

                $cashier = CashCashier::where([['_cash',$idcash],['created_at',$date],['id_tpv',$idtpv]])->update($upd);
                if($cashier == 1){
                    $res = [
                        "close"=>true,
                        "message"=>'Caja cerrada'
                    ];
                    return response()->json($res);
                }else{
                    $res = [
                        "close"=>false,
                        "message"=>'No se logro actualizar la caja'
                    ];
                    return response()->json($res);
                }
            }else{
                $res = [
                    "close"=>false,
                    "message"=>'No se actualizo el estado de la caja x('
                ];
                return response()->json($res);
            }
        }else{
            return responser()->json($close,500);
        }
    }

    public function automateCash(){}

    public function getDocument(){
        $document = DocumentType::all();
        return response()->json($document);
    }

    public function editDocument(Request $request){
        $document = $request->all();
        if($document['id']){
            $upd = DocumentType::find($document['id']);
            $upd->name = $document['name'];
            $upd->serie = $document['serie'];
            $upd->save();
            $res = $upd->fresh();
            if($upd){
                return response()->json($res,200);
            }else{
                return response()->json('No se logro actualizar el documento',500);
            }
        }else{
            $newdoc = new DocumentType;
            $newdoc->name=$document['name'];
            $newdoc->serie=$document['serie'];
            $newdoc->save();
            $res = $newdoc->fresh();
            if($newdoc){
                return response()->json($res,200);
            }else{
                return response()->json('No se logro Insertar el documento',500);
            }
        }
    }

    public function getTPV(){
        $clients = Client::where('_type',1)->get();
        $warehouses = Warehouse::with(['store'])->where([['_type',1],['_state',0]])->get();
        $tpvs = TPV::with(['warehouse.store','client'])->get();
        $res = [
            'clients'=>$clients,
            'warehouses'=>$warehouses,
            'tpv'=>$tpvs
        ];
        return response()->json($res);
    }

    public function addTPV(Request $request){
        $tpv = new TPV;
        $tpv->name = $request->name;
        $tpv->_client = $request->_client;
        $tpv->_warehouse = $request->_warehouse;
        $tpv->header_ticket = $request->header_ticket;
        $tpv->footer_ticket = $request->footer_ticket;
        $tpv->logo = '';
        $tpv->save();
        $res = $tpv->load(['warehouse.store','client']);
        if ($res) {
            $uid = $res['id'];
            $folderPath = public_path('multimedia/tpv/'.$uid.'/');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
            if ($request->hasFile('logo')) {
                $avatar = $request->file('logo');
                $avatarPath = $folderPath . '/' . $avatar->getClientOriginalName();
                $avatar->move($folderPath, $avatar->getClientOriginalName());
                $tpv->logo = $avatar->getClientOriginalName();
                $tpv->save();
            }
            return response()->json($res,200);
        }else{
            return response()->json('Hubo un problema en la insercion',500);
        }
    }

    public function editTPV(Request $request){
        $tpv = TPV::find($request->id);

        $tpv->name = $request->name;
        $tpv->_client = $request->_client;
        $tpv->_warehouse = $request->_warehouse;
        $tpv->header_ticket = $request->header_ticket;
        $tpv->footer_ticket = $request->footer_ticket;

        if ($request->hasFile('logo')) {
            if ($tpv->logo) {
                $oldLogoPath = public_path('multimedia/tpv/' . $tpv->id . '/' . $tpv->logo);
                if (file_exists($oldLogoPath)) {
                    unlink($oldLogoPath);
                }
            }

            $folderPath = public_path('multimedia/tpv/'.$tpv->id.'/');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            $avatar = $request->file('logo');
            $avatarPath = $folderPath . '/' . $avatar->getClientOriginalName();
            $avatar->move($folderPath, $avatar->getClientOriginalName());
            $tpv->logo = $avatar->getClientOriginalName();
        }

        $tpv->save();
        $res = $tpv->load(['warehouse.store','client']);

        if ($res) {
            return response()->json($res,200);
        } else {
            return response()->json('Hubo un problema en la insercion',500);
        }
    }

    public function mosFIle($id){
        $tpv = TPV::find($id);
        $folderName = $tpv->logo;
        $folderPath = public_path("multimedia/tpv/{$tpv->id}/{$folderName}");
        if (file_exists($folderPath)) {
            return response()->file($folderPath);
        }
    }

    public function editCash(Request $request){

        $suc = Store::where('id',1)->first();

        $cash = $request->all();
        if($cash['id']){
            $update = CashRegister::find($cash['id']);
            $update->name = $cash['name'];
            $update->_store = $cash['_store'];
            $update->_tpv = $cash['tpv']['id'];
            $update->_document = $cash['document']['id'];
            $update->save();
            $res = $update->load(['state','tpv','document']);
            if($res){
                return response()->json($res,200);
            }else{
                return response()->json('No se realizo la actualizacion',500);
            }
        }else{
            $suc = Store::where('id',1)->first();
            $obtUltTermi = Http::get($suc->local_domain.':'.$suc->local_port.'/addicted/public/api/cash/getMaxTer');
            if($obtUltTermi->status() == 200){
                $idter=$obtUltTermi['ID']+1;
            }else{
                return response()->json(["mssg"=>'No se obtuvo el ultimo numero de terminal'],500);
            };
            $cashier = new CashRegister;
            $cashier->name = $cash['name'];
            $cashier->terminal = $idter;
            $cashier->_store = $cash['_store'];
            $cashier->_state = 1;
            $cashier->_tpv = $cash['tpv']['id'];
            $cashier->_document = $cash['document']['id'];
            $cashier->save();
            $res = $cashier->load(['state','document','tpv']);
            if($res){
                $addTer = Http::post($suc->local_domain.':'.$suc->local_port.'/addicted/public/api/cash/addTerm',$res);
                if($addTer->status() == 200){
                }else{
                    return response()->json(["mssg"=>'No se obtuvo el Inserto de terminal'],500);
                };
                return response()->json($res,200);
            }else{
                return response()->json('No se inserto',500);
            }
        }
    }

    public function getCashAssigned(Request $request){
        $uid = $request->fixeds->uid;
        $store = $request->route('sid');
        $now = now()->format('Y-m-d');
        $cash = CashRegister::with([
            'state',
            'document',
            'tpv',
            'cashier'
        ])
        ->where([['_store', $store],['_state',1]])
        ->whereHas('cashier', function($q) use($uid,$now) { $q->with('user', 'printer', 'printer_order')->where('_cashier', $uid)->whereDate('created_at',$now);})
        ->first();

        return response()->json($cash,200);
    }
}

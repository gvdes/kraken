<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\ClientState;
use App\Models\PricesRates;
use App\Models\ClientLog;
use App\Models\Store;
use Illuminate\Support\Facades\Http;




class ClientController extends Controller
{
    public function getClients(){
        $clients = Client::with(['state','type','rate'])->get();
        $types = ClientType::all();
        $rate = PricesRates::all();
        $state = ClientState::all();
        $res = [
            "clients"=>$clients,
            "types"=>$types,
            "rates"=>$rate,
            "state"=>$state
        ];
        return response()->json($res);
    }

    public function editClient(Request $request){
        $response = [
            "goals"=>[],
            "fails"=>[]
        ];
        $addicted = env('ADDICTED');
        $user = $request->fixeds->uid;
        if(isset($request->id)){// edicion de cliente
        $client = Client::find($request->id);
        $client->name = $request->name;
        $client->address = json_encode($request->address);
        $client->phone = $request->phone;
        $client->celphone = $request->celphone;
        $client->mail = $request->mail;
        $client->_type = $request->type['id'];
        $client->_rate = $request->rate['id'];
        $client->_state = $request->state['id'];
        $client->save();
        $res = $client->load(['state','type','rate']);
        if($res){
            $log = new ClientLog();
            $log->_client = $res->id;
            $log->_user = $user;
            $log->_type = 14;
            $log->details = json_encode($res);
            $inlog = $log->save();
            if($inlog){
                $stores = Store::where('_state',1)->get();
                foreach($stores as $store){
                    $url = $store->local_domain.':'.$store->local_port.$addicted.'clients/editClient';
                    $addingClient = Http::post($url,$res);
                    if($addingClient->status() == 200){
                        $response['goals'][]= ["IdSucursal"=>$store->id, "Sucursal"=>$store->name, "Cliente"=>$res->id, "message"=>json_decode($addingClient)];
                    }else{
                        $response['fails'][]= ["IdSucursal"=>$store->id, "Sucursal"=>$store->name, "Cliente"=>$res->id, "message"=>json_decode($addingClient)];
                    }
                }
                $response['res']=$res;
                return response()->json($response,200);
            }
        }
        }else{
            $fs_id  = Client::max('fs_id') + 1;
            $client = new Client();
            $client->name = $request->name;
            $client->fs_id = $fs_id;
            $client->address = json_encode($request->address);
            $client->phone = $request->phone;
            $client->celphone = $request->celphone;
            $client->mail = $request->mail;
            $client->_payment = 4;
            $client->_type = $request->type['id'];
            $client->_rate = $request->rate['id'];
            $client->_state = $request->state['id'];
            $client->save();
            $res = $client->load(['state','type','rate']);
            if($res){
                $log = new ClientLog();
                $log->_client = $res->id;
                $log->_user = $user;
                $log->_type = 13;
                $log->details = json_encode($res);
                $inlog = $log->save();
                if($inlog){
                    $stores = Store::where('_state',1)->get();
                    foreach($stores as $store){
                        $url = $store->local_domain.':'.$store->local_port.$addicted.'clients/addingClient';
                        $addingClient = Http::post($url,$res);
                        if($addingClient->status() == 200){
                            $response['goals'][]= ["IdSucursal"=>$store->id, "Sucursal"=>$store->name, "Cliente"=>$res->id, "message"=>json_decode($addingClient)];
                        }else{
                            $response['fails'][]= ["IdSucursal"=>$store->id, "Sucursal"=>$store->name, "Cliente"=>$res->id, "message"=>json_decode($addingClient)];
                        }
                    }
                    $response['res']=$res;
                    return response()->json($response,200);
                }
            }
        }
    }

    public function replyClient(Request $request){
        $addicted = env('ADDICTED');
        $store = Store::find($request->IdSucursal);
        $client = Client::with(['state','type','rate'])->where('id',$request->Cliente)->first();
        $edit = $request->edit;
        if($edit){
            $url = $store->local_domain.':'.$store->local_port.$addicted.'clients/editClient';
        }else{
            $url = $store->local_domain.':'.$store->local_port.$addicted.'clients/addingClient';
        }
        $synCli = Http::post($url,$client);
        if($synCli->status() == 200){
            return response()->json(["state"=>true]);
        }else{
            return response()->json(["state"=>false]);
        }

    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreStates;
use App\Models\StoreTypes;
use App\Models\PricesTypes;
use App\Models\Seasons;
use App\Models\StoresSeasons;
use Illuminate\Support\Facades\Http;




class StoresController extends Controller
{
    public function getStores(Request $request){
        $tipo = $request->query('type');
        $stores = Store::with('storeSeasons','state','type','price','users.rol.area')->get();
        $states = StoreStates::all();
        $types = StoreTypes::all();
        $prices = PricesTypes::all();
        $seasons = Seasons::all();
        $res = [
            "states"=>$states,
            "types"=>$types,
            "prices"=>$prices,
            "seasons"=>$seasons,
        ];
        if($tipo == 1){
            $res['stores'] = $this->Pings($stores);
        }else{
            $res['stores'] = $stores;
        }
        return response()->json($res,200);
}

    public function addStore(Request $request){
        $store = new Store();
        $store->name = $request->name;
        $store->alias = $request->alias;
        $store->domain = $request->domain;
        $store->port = $request->port;
        $store->local_domain = $request->local_domain;
        $store->local_port = $request->local_port;
        $store->_state = $request->state['id'];
        $store->_type = $request->type['id'];
        $store->_price_type = $request->price['id'];
        $store->access_file = $request->access_file;
        $store->save();
        $res = $store->fresh()->toArray();
        if($res){
            $seasons = StoresSeasons::where('_store',$res['id'])->whereIn('_season',$request->groupoption);
            $seasons->update(['_state'=>1]);
            return response()->json($res,200);
        }else{
            return response()->json('No fue posible crear el usuario',400);
        }
    }

    public function Pings($stores){
        foreach($stores as $store){
            try{
                $url = $store['local_domain'].':'.$store['local_port'].'/Addicted/public/api/resources/ping';
                $ping = Http::timeout(1)->get($url);
                if($ping->status() == 200){
                    $store['ping'] = true;
                }else{
                    $store['ping'] = false;
                }
            }catch (\Illuminate\Http\Client\ConnectionException $e){
                $store['ping'] = false;
            }
        }
        return $stores;
    }

    public function updateStore(Request $request){
        $store = Store::find($request->id);
        $store->name = $request->name;
        $store->alias = $request->alias;
        $store->domain = $request->domain;
        $store->port = $request->port;
        $store->local_domain = $request->local_domain;
        $store->local_port = $request->local_port;
        $store->_state = $request->_state;
        $store->_type = $request->_type;
        $store->_price_type = $request->_price_type;
        $store->access_file = $request->access_file;
        $store->save();
        $res = $store->fresh()->toArray();
        if($res){
            $seasons = $request->store_seasons;
            foreach($seasons as $season){
                $storeseason = StoresSeasons::where('_store',$request->id)->where('_season',$season['_season']);
                $storeseason->update(['_state'=>$season['_state']]);
            }
            return response()->json($res,200);
        }else{
            return response()->json('Hubo un problema en la actualizacion',400);
        }
    }
}

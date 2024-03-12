<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Provider;
use App\Models\ProviderType;

class ProvidersController extends Controller
{
    public function getProviders(Request $request){
        $providers = Provider::with('type')->get();
        $types = ProviderType::all();
        $res = [
            "providers"=>$providers,
            "types"=>$types
        ];
        return response()->json($res,200);
    }

    public function create(Request $request){
        $provider = new Provider;
        $provider->fiscal_name = $request->fiscal_name;
        $provider->address = json_encode($request->address);
        $provider->_type = $request->_type['id'];
        $provider->_state = 1;
        $provider->save();
        if($provider){
            $res = $provider->fresh()->toArray();
            return response()->json($res,200);
        }else{
            return response()->json("No se realizo el proveedor",401);
        }
    }

    public function update(Request $request){
        $provider = Provider::find($request->id);
        $provider->fs_id = $request->fs_id;
        $provider->fiscal_name = $request->fiscal_name;
        $provider->address = $request->address;
        $provider->_type = $request->_type;
        $provider->_state = $request->_state;
        $provider->save();
        $provider->fresh()->toArray();
        return $provider;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssistDevice;
use App\Models\Store;
use Rats\Zkteco\Lib\ZKTeco;

class AssistController extends Controller
{
    public function Index(){
        $devices = AssistDevice::with('store')->get();
        return response()->json($devices,200);
    }

    public function ping($d){
        $zk = new ZKTeco($d);

        if($zk->connect()){
            return response()->json(true,200);
        }else{
            return response()->json(false,200);
        }
    }
    public function pingNew($d){
        $zk = new ZKTeco($d);

        if($zk->connect()){
            $number_serie = ltrim(stristr($zk->serialNumber(),'='),'=');
            $name = ltrim(stristr($zk->deviceName(),'='),'=');
            $res = [
                "serial_number"=>$number_serie,
                "name"=>$name
            ];
            return response()->json($res,200);
        }else{
            return response()->json(false,401);
        }
    }

    public function edit(Request $request){
        $ip = $request->ip;
        $name = $request->nick_name;
        $id = $request->id;
        $device = AssistDevice::find($id);
        if($device){
            $device->ip = $ip;
            $device->nick_name = $name;
            $device->save();
            $device->fresh();
            return response()->json($device,200);
        }else{
            return response()->json('No existe el dispositivo',404);
        }
    }

    public function new(){
        $devices = AssistDevice::all();
        $store = Store::all();
        $res = [
            "devices"=>$devices,
            "stores"=>$store
        ];
        return response()->json($res,200);
    }

    public function addDevice(Request $request){
        $device = new AssistDevice;
            $device->ip =$request->ip;
            $device->name =$request->name;
            $device->nick_name=$request->nick_name;
            $device->serial_number=$request->serial_number;
            $device->_store=$request->_store['id'];
        $device->save();
        $res = $device->fresh();
        return response()->json($res,200);
    }

}

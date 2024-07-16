<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssistDevice;
use App\Models\Store;
use App\Models\AssistJustification;
use App\Models\JustificationState;
use App\Models\JustificationType;
use App\Models\PaymenPercentage;
use App\Models\User;
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

    public function form(Request $request){
        $store = $request->route('sid');
        $staff = User::where('_store',$store)->get();
        return response()->json($staff);
    }

    public function addFile(Request $request){
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName =$request->idms."-".$file->getClientOriginalName();
                $file->move(public_path('multimedia'), $fileName);
                return response()->json(['message' => $fileName]);
            }
            return response()->json(['message' => $request->all()], 400);
    }

    public function addForm(Request $request){
        $jstf = $request->all();
        $justification = new AssistJustification;
        $justification->_user = $jstf['user']['id'];
        $justification->created_at  = now();
        $justification->start_date = $jstf['start_date'];
        $justification->final_date = $jstf['final_date'];
        $justification->notes = $jstf['notes'];
        $justification->evidence = $jstf['evidence'];
        $justification->save();

        $res = $justification->fresh()->toArray();
        if($res){
            return response()->json($res,200);
        }else{
            return response()->json('No se pudo crear la justificacion',400);
        }
    }

    public function getJustifications(){
        $justifications = AssistJustification::with('user','paymen','type','state')->get();
        $types = JustificationType::all();
        $percentage = PaymenPercentage::all();
        $states = JustificationState::all();
        $res = [
            "justifications"=>$justifications,
            "types"=>$types,
            "porcentages"=>$percentage,
            "states"=>$states
        ];
        return response($res,200);
    }

    public function changeStatus(Request $request){
        $justification = AssistJustification::find($request->id);
        $justification->_type = $request->type['id'];
        $justification->_pay_percentage = $request->paymen['id'];
        $justification->_state = $request->state['id'];
        $justification->save();
        $res = $justification->load(['user','paymen','type','state']);
        return response()->json($res, 200);
    }

}

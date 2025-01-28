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
use App\Models\Assist;
use App\Models\ViewReportWeek;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\DB;

class AssistController extends Controller
{
    public function Index(){
        $devices = AssistDevice::with('store')->get();
        return response()->json($devices,200);
    }

    public function ping($d){
        $device = AssistDevice::find($d);
        $zk = new ZKTeco($device->ip);

        if($zk->connect()){
            $date = $zk->getTime();
            $current = date('Y-m-d H:i:s');
            $register = $zk->getAttendance();
            $res = [
                "connect"=>true,
                "date"=>$date,
                "register"=>count($register),
                "current" => $current
            ];
            $zk->disconnect();
            return response()->json($res,200);
        }else{
            $res = [
                "connect"=>false,
                "date"=>'Sin Conexion',
                "register"=>'Sin Conexion',
                "current"=>"Sin Conexion"
            ];
            // $zk->disconnect();
            return response()->json($res,200);
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
        $types = JustificationType::all();
        $res = [
            'user'=>$staff,
            'types'=>$types
        ];
        return response()->json($res);
    }

    public function addForm(Request $request){
        $jstf = $request->all();
        $justification = new AssistJustification;
        $justification->_user = $jstf['user'];
        $justification->created_at  = now();
        $justification->start_date = $jstf['start_date'];
        $justification->final_date = $jstf['final_date'];
        $justification->_type = $jstf['_type'];
        $justification->notes = $jstf['notes'];
        if ($request->hasFile("evidence")) {
            $folderName = uniqid();
            $folderPath = public_path('multimedia/profiles/'.$jstf['user'].'/justifications/'.$folderName);
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
            $files = $request->file("evidence");
            foreach ($files as $file) {
                $fileName = $file->getClientOriginalName();
                $file->move($folderPath, $fileName);
            }
            $justification->evidence = $folderName;
        }
        $justification->save();
        $res = $justification->fresh()->toArray();
        if($res){
            return response()->json($res,200);
        }else{
            return response()->json('No se pudo crear la justificacion',400);
        }
    }

    public function getJustifications(){
        $justifications = AssistJustification::with('user','paymen','type','state')->where('evidence','!=','')->get();

        foreach($justifications as $justification){
            $userId = $justification['_user'];
            $folderName = $justification['evidence'];
            $folderPath = public_path("multimedia/profiles/{$userId}/justifications/{$folderName}");

            if (!file_exists($folderPath) || !is_dir($folderPath)) {
                $justification['files'] = [];
            }
            $files = array_values(array_diff(scandir($folderPath), ['.', '..'])); // Excluye `.` y `..`

            $filesWithUrls = array_map(function ($file) use ($userId, $folderName) {
                    return  "profiles/{$userId}/justifications/{$folderName}/{$file}";
            }, $files);
            $justification['files']= $filesWithUrls;
        }
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

    public function getRegisDevice($d){
        $goals = [];
        $fails = [];
        $report = [];
        $device = AssistDevice::find($d);
        $zk = new ZKTeco($device->ip);
        $exist = Assist::with('user')->where('_device',$device->id)->get()->toArray();
        $rexist  = array_map(function($val)
        {
            return [
                'auid'=>$val['auid'],
                'id'=>$val['user']['RC_id'],
                'state'=>$val['_class'],
                'timestamp'=>$val['register'],
                'type'=>$val['_type']
            ];
        }
                ,$exist);
        if($zk->connect()){
             $assists = $zk->getAttendance();
            if($assists){
                $dev = array_map(function($val){return implode(',',$val);},$assists);
                $dba = array_map(function($val){return implode(',',(array)$val);},$rexist);
                $diff = array_diff($dev, $dba);
                $vdiff = array_values($diff);
                $diferencias = array_map(function($val){ return explode(',',$val);} ,$vdiff);
                // return $diferencias;
                if($diferencias){
                    foreach($diferencias as $assist){
                        $user = User::where('RC_id',$assist[1])->first();
                        if($user){
                            $report [] = [
                                "auid" => $assist['0'],//id checada checador
                                "register" => $assist['3'], //horario
                                "_user" => $user->id,//id del usuario
                                "_store"=> $device->_store,
                                "_type"=>$assist['4'],//entrada y salida
                                "_class"=>$assist['2'],//condedo o contrasena
                                "_device"=>$device->id,
                            ];
                        }else{
                            // $finduser = $zk->getUser();
                            // $find = array_values(array_filter($finduser, function($val) use($assist){ return $val['userid'] == $assist[1];}));
                            // $fails[]=$device->nick_name." no existe el id ".$assist[1]." con el nombre ".$find[0]['name']." favor de revisar ";
                        }

                    }
                    $insert = Assist::insert($report);
                    // return $report;
                    if($insert){
                        $goals[] = $device->nick_name." se insertaron ".count($report)." registros";
                    }
                }else{
                    $goals[] = $device->nick_name." No hay registros";
                }
            }
            $zk->disconnect();
            $res = ["goals"=>$goals, "fails"=>$fails];
            return response()->json($res, 200);
        }else{
            return response()->json('Sin Conexion',200);
        }
    }

    public function changeDate($d){
        $device = AssistDevice::find($d);
        // return $device;
        $zk = new ZKTeco($device->ip);
        if($zk->connect()){
            $date= date('Y-m-d H:i:s');
            $zk->setTime($date);
            $zk->disconnect();
            $res = [
                "change"=>true,
                "date"=>$date,
            ];
            return response()->json($res,200);
        }else{
            $res = [
                "change"=>false,
                "date"=>'Sin Conexion',
            ];
            return response()->json($res,401);
        }
    }

    public function deleteAttendance($d){
        $device = AssistDevice::find($d);
        $zk = new ZKTeco($device->ip);
        if($zk->connect()){
            $zk->clearAttendance();
            $zk->disconnect();
            $res = [
                "delete"=>true,
                "mssge"=>"Se eliminaron los registros"
            ];
            return response()->json($res,200);
        }else{
            $res = [
                "delete"=>false,
                "mssge"=>"No Se eliminaron los registros"
            ] ;
            return response()->json($res,401);
        }
    }

    public function getReportWeek(){
        $report = ViewReportWeek::select('*',
        DB::raw('faltas(LUNES) +  faltas(MARTES) +faltas(MIERCOLES) +faltas(JUEVES) +faltas(VIERNES) +faltas(SABADO) +faltas(DOMINGO)  AS FALTAS'),
        DB::raw('retardos(LUNES) + retardos(MARTES) + retardos(MIERCOLES) + retardos(JUEVES) + retardos(VIERNES) + retardos(SABADO) + retardos(DOMINGO) AS RETARDOS'),
        DB::raw('vacaciones(LUNES) + vacaciones(MARTES) + vacaciones(MIERCOLES) + vacaciones(JUEVES) + vacaciones(VIERNES) + vacaciones(SABADO) + vacaciones(DOMINGO)  AS VACACIONES'))
        ->get();
        $devices = AssistDevice::all();
        $res = [
            "report"=>$report,
            "devices"=>$devices
        ];
        return response()->json($res,200);
    }

}

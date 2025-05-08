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
use App\Models\Turn;
use App\Models\Fecha;
use App\Models\Proceeding;
use App\Models\ViewReportWeek;
use App\Models\UserRol;
use App\Models\ConfigWapi;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssistController extends Controller
{
    public function Index(){
        $devices = AssistDevice::with('store')->get();
        return response()->json($devices,200);
    }

    public function ping($d){
        $device = AssistDevice::find($d);
        // return $d;
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

    public function pingStore($sid,$d){
        $device = AssistDevice::find($d);
        // return $d;
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
        $uid = $request->fixeds;
        $area = UserRol::with('area')->where('id',$uid->rol)->first();
        $staff = User::with('rol')->where([['_store',$store],['_state','!=',4]])->whereHas('rol', function($q) use($area) { $q->where('_area',$area->area['id']); })->get();
        $types = JustificationType::all();
        // $RcIds = implode(',',$staff->pluck('id')->toArray());
        $justifications = AssistJustification::with('user','paymen','type','state')->where('evidence','!=','')->whereIn('_user',$staff->pluck('id')->toArray())->whereRaw('WEEK(( created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY), 7) = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
        ->whereRaw('YEAR(created_at) = YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))')->get();

        $res = [
            'user'=>$staff,
            'types'=>$types,
            'justifications'=>$justifications
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
            // $folderPath = public_path('multimedia/profiles/'.$jstf['user'].'/justifications/'.$folderName);
             $folderPath = 'multimedia/profiles/'.$jstf['user'].'/justifications/'.$folderName;

            // if (!file_exists($folderPath)) {
            //     mkdir($folderPath, 0777, true);
            // }
            $files = $request->file("evidence");
            foreach ($files as $file) {
                $fileName = $file->getClientOriginalName();
                $route = Storage::put($folderPath . '/' . $fileName, file_get_contents($file));
                // $fileName = $file->getClientOriginalName();
                // $file->move($folderPath, $fileName);
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
        $fechas = Fecha::select('*',
        DB::raw(' WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) AS week'),
        DB::raw(' YEAR(fecha) AS anio')
        )->orderBy('fecha','ASC')->get();


        $justifications = AssistJustification::with('user','paymen','type','state')->where('evidence','!=','')->whereRaw('WEEK(( created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY), 7) = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
        ->whereRaw('YEAR(created_at) = YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))')->get();

        foreach($justifications as $justification){
            $userId = $justification['_user'];
            $folderName = $justification['evidence'];

            // $folderPath = public_path("multimedia/profiles/{$userId}/justifications/{$folderName}");
            $folderPath = "multimedia/profiles/{$userId}/justifications/{$folderName}";
            $files = Storage::files($folderPath);

            $filesWithUrls = collect($files)->map(function ($path) {
                return [
                    'path' => $path,
                    // 'url' => Storage::temporaryUrl($path, now()->addMinutes(10))
                    'url' => Storage::Url($path)

                ];
            });

            $justification['files'] = $filesWithUrls;

            // if (!file_exists($folderPath) || !is_dir($folderPath)) {
            //     $justification['files'] = [];
            // }
            // $files = array_values(array_diff(scandir($folderPath), ['.', '..'])); // Excluye `.` y `..`

            // $filesWithUrls = array_map(function ($file) use ($userId, $folderName) {
            //         return  "profiles/{$userId}/justifications/{$folderName}/{$file}";
            // }, $files);
            // $justification['files']= $filesWithUrls;
        }
        $types = JustificationType::all();
        $percentage = PaymenPercentage::all();
        $states = JustificationState::all();

        $res = [
            "justifications"=>$justifications,
            "types"=>$types,
            "porcentages"=>$percentage,
            "states"=>$states,
            "fechas"=>$fechas
        ];
        return response($res,200);
    }

    public function getFiltJustifications(Request $request){

        $anio = $request->anio;
        $min = $request->min;
        $max = $request->max;


        $justifications = AssistJustification::with('user','paymen','type','state')->where('evidence','!=','')->whereRaw('WEEK(( created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY), 7) BETWEEN ? AND ? ', [$min, $max])
        ->whereRaw('YEAR(created_at) = ?', [$anio])->get();

        foreach($justifications as $justification){
            $userId = $justification['_user'];
            $folderName = $justification['evidence'];
            // $folderPath = public_path("multimedia/profiles/{$userId}/justifications/{$folderName}");
            $folderPath = "multimedia/profiles/{$userId}/justifications/{$folderName}";
            $files = Storage::files($folderPath);
            $filesWithUrls = collect($files)->map(function ($path) {
                return [
                    'path' => $path,
                    // 'url' => Storage::temporaryUrl($path, now()->addMinutes(10))
                    'url' => Storage::Url($path)

                ];
            });

            $justification['files'] = $filesWithUrls;


            // if (!file_exists($folderPath) || !is_dir($folderPath)) {
            //     $justification['files'] = [];
            // }
            // $files = array_values(array_diff(scandir($folderPath), ['.', '..'])); // Excluye `.` y `..`

            // $filesWithUrls = array_map(function ($file) use ($userId, $folderName) {
            //         return  "profiles/{$userId}/justifications/{$folderName}/{$file}";
            // }, $files);
            // $justification['files']= $filesWithUrls;
        }
        return response($justifications,200);
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
        $date = now()->format('Y-m-d');
        $fechas = Fecha::select('*',
        DB::raw(' WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) AS week'),
        DB::raw(' YEAR(fecha) AS anio')
        )->orderBy('fecha','ASC')->get();

        $filtradas = $fechas->filter(fn($item) => $item->fecha == $date);
        $oing = $filtradas->last();
        $report =  DB::select("CALL obtReport(?, ?, ?)", [ $oing->week,  $oing->week,  $oing->anio]);
        $devices = AssistDevice::all();
        $res = [
            "report"=>$report,
            "devices"=>$devices,
            "fechas"=>$fechas,
        ];
        return response()->json($res,200);
    }

    public function getFiltReport(Request $request){
        $report =  DB::select("CALL obtReport(?, ?, ?)", [ $request->min,  $request->max,  $request->anio]);
        return response()->json($report,200);
    }

    public function getReportUserWeek(Request $request){
        $user = $request->user;
        $date = now()->format('Y-m-d');
        $fechas = Fecha::select('*',
        DB::raw(' WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) AS week'),
        DB::raw(' YEAR(fecha) AS anio')
        )->orderBy('fecha','ASC')->get();

        $filtradas = $fechas->filter(fn($item) => $item->fecha == $date);
        $oing = $filtradas->last();
        $report =  collect(DB::select("CALL obtReport(?, ?, ?)", [ $oing->week,  $oing->week,  $oing->anio]))->where('ID', $user);
        $res = [
            "report"=>array_values($report->toArray()),
            "fechas"=>$fechas,
        ];
        return response()->json($res,200);
    }

    public function getReportUserWeekFilt(Request $request){
        // return $request->all();
        $user = $request->user;
        $inicio = $request->min;
        $final = $request->max;
        $year = $request->anio;
        $rawReport = collect(DB::select("CALL obtReport(?, ?, ?)", [$inicio, $final, $year]))
        ->where('ID', $user)->toArray();

        return response()->json(array_values($rawReport),200);
    }

    public function addProceedings(Request $request){
        $insert =  $request->all();
        $adding = Proceeding::insert($insert);
        if($adding){
            return response()->json('Se inserto el acta',200);
        }else{
            return response()->json('No se inserto el acta',500);
        }
    }

    public function getTurnsWeek(Request $request){
        $sid = $request->route('sid');
        $staff = User::where('_store',$sid)->get();
        $turn = Turn::with([
            'users' ])
        ->whereHas('users', function($q) use ($sid) { $q->where('_store', $sid);
        })->whereRaw('_week = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
        ->whereRaw('_year = YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))')
        ->get();
        $res = [
            "users"=>$staff,
            "turns"=>$turn
        ];
        return response()->json($res);
    }

    public function addTurnsWeek(Request $request){
        $goals = [
            'eliminado'=>[],
            'creado'=>[]
        ];
        $fails = [];

        $turnos = $request->turns;
        // return $turnos;
        foreach($turnos as $turno){
            $exitTurn = Turn::where([['_user',$turno['_user']],['hour_hand',$turno['hour_hand']]])->whereRaw('_week = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
            ->whereRaw('_year = YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))')->first();
            if($exitTurn){
                $fails[]=$turno;
            }else{
                $busTurn = Turn::where('_user', $turno['_user'])
                ->whereRaw('_week = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
                ->whereRaw('_year = YEAR(CURDATE())')
                ->delete();
                if($busTurn){
                    $goals['eliminado'][] = $turno;
                }
                $goals['creado'][] = $turno;
                Turn::create([
                    '_week' => DB::raw('WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)'),
                    '_year' => DB::raw('YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))'),
                    '_user' => $turno['_user'],
                    'hour_hand' => $turno['hour_hand']
                ]);
            }
        }
        $res = [
            "goals"=>$goals,
            "fails"=>$fails
        ];
        return response()->json($res);
    }

    public function deleteTurnUser(Request $request){
        $goals = [
            'eliminado'=>[],
        ];
        $hour_hand=$request->key;
        $user = $request->user;

        $exitTurn = Turn::where([['_user',$user],['hour_hand',$hour_hand]])->whereRaw('_week = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
        ->whereRaw('_year = YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))')->first();
        if($exitTurn){
            $busTurn = Turn::where([['_user',$user],['hour_hand',$hour_hand]])
                ->whereRaw('_week = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)')
                ->whereRaw('_year = YEAR(CURDATE())')
                ->delete();
            if($busTurn){
                $goals['eliminado']=['_user'=>$user,'hour_hand'=>$hour_hand];
            }
        }
        return response()->json($goals,200);
    }

    public function getRegisDeviceStore($sid,$d){
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

    public function changeDateStore($sid,$d){
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

    public function ReplyAssistAut(){
        $goals = [];
        $fails = [];
        $report = [];
        $devices = AssistDevice::with('store')->get();
        if(count($devices) > 0 ){
            foreach($devices as $device){
                $inicio = microtime(true);
                echo 'actualizando '.$device->nick_name." \n";
                $zk = new ZKTeco($device->ip);
                $exist = Assist::with('user')->where('_device',$device->id)->get()->toArray();
                $rexist  = array_map(function($val)
                { return [
                        'auid'=>$val['auid'],
                        'id'=>$val['user']['RC_id'],
                        'state'=>$val['_class'],
                        'timestamp'=>$val['register'],
                        'type'=>$val['_type']
                    ];
                },$exist);

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
                                }
                            }
                            $insert = Assist::insert($report);
                            // return $report;
                            if($insert){
                                $goals = $device->nick_name." se insertaron ".count($report)." registros";
                            }else{
                                $fails = $device->nick_name."no se insertaron ".count($report)." registros";
                            }
                        }else{
                            $goals = $device->nick_name." No hay registros";
                        }
                    }else{
                        $goals = $device->nick_name."No hay resgistros";
                    }
                    $termino = microtime(true);
                    $zk->disconnect();
                    $res = ["goals"=>$goals, "fails"=>$fails , "Dispositivo" => $device->nick_name, 'tiempo'=>round($termino-$inicio,2)];
                    echo json_encode($res)." \n";

                }else{
                    $termino = microtime(true);
                    $message = 'El dispositivo '.$device->nick_name.' no tiene conexion :('." \n";
                    $msg = $this->msg($message);
                    if($msg){
                        echo 'Mensaje Enviado'.' tiempo :'.round($termino-$inicio)." \n";
                    }else{
                        echo 'No se envio el mensaje'." \n";
                    }
                }
                $goals=[];
                $fails=[];
                $report=[];
            }

        }else{
            echo 'No hay Dispositivos brou';
        }
    }

    public function msg($message){
        $wapi = ConfigWapi::find(1);
        $token = $wapi->token;
        $instance = $wapi->id_instance;
        $params=array(
            'token' => $token ,
            'to' => '5539297483',
            'body' => $message
            );
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.ultramsg.com/".$instance."/messages/chat",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => http_build_query($params),
              CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return false;
            } else {
                return true;
            }
    }

}

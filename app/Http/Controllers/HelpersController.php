<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Str;


class HelpersController extends Controller
{

    private function ping($store){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://{$store->dominio}/ness/public/api/");
        // curl_setopt($ch, CURLOPT_URL, "http://192.168.10.176/ness/public/api/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $exec = json_decode(curl_exec($ch));
        $info = curl_getinfo($ch);

        return curl_errno($ch) ? ["error"=>curl_error($ch)] : ["error"=>false,"done"=>$exec,"info"=>$info];

        curl_close($ch);
    }

    public function pinger(Request $request){
        $reqstores = $request->stores;
        $dones = [];
        $fails = [];

        $conntable = DB::connection('vtest')->table('workpoints')->where([
            ["_type",2],
            ["active",1]
        ]);

        if($reqstores){ $conntable->whereIn("id",$reqstores); }

        // $stores = $conntable->get();//activar para modo produccion
        $stores = $conntable->get()->map(function($s){ $s->dominio="192.168.12.183"; return $s; });

        foreach($stores as $store){
            $ping = $this->ping($store);

            if($ping["error"]){
                $fails[$store->alias]= $ping["error"];
            }else{
                $dones[$store->alias] = "{$ping['done']->resp}";
            }
        }

        // return response()->json(["dones"=>$dones,"fails"=>$fails,"storesreq"=>$reqstores,"stores"=>$stores]);// esta linea incluye mucha info descomentar si se requieren mas detalles
        return response()->json(["dones"=>$dones,"fails"=>$fails]);
    }

    public function genpass(Request $request){
        $str = $request->route("str");

        $newPass = Hash::make($str);

        return response()->json([
            "hash" => $newPass
        ]);
    }

    public function twiliotest(){
        // $date = Carbon::now()->format("Y-m-d H:i:s a");
        $twisid = env("TWILIO_SID");;
        $twitkn = env("TWILIO_AUTH_TOKEN");

        try {
            $twcli = new TwilioClient($twisid,$twitkn);
            $twcli->messages->create('+525574877504',['from'=>"+525591620437",'body'=>"Hello from VizApp your auth code is 756423"]);

            return response()->json([$twisid,$twitkn]);
        } catch (\Throwable $th) { return response()->json($th->getMessage(),400); }

        // return response()->json([ "date"=>$date ]);
    }

    public function genUuid(){
        $uuid = Str::uuid();
        return response()->json(["uuid"=>$uuid]);
    }
}

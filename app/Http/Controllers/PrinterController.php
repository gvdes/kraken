<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Order;
use App\Models\Store;
use App\Models\UnitMeassure;
use App\Models\Warehouse;
use App\Models\OrderLog;
use App\Models\OrderBodie;
use App\Models\Seasons;
use App\Models\SeassonBussinesRules;
use App\Models\Printer;
use App\Models\PrinterTypes;
use App\Models\OrderStateConfig;
use App\Models\CashRegister;
use Carbon\Carbon;

class PrinterController extends Controller
{

    public function index(){
        $prints = Store::with(['prints.type'])->get();
        $types = PrinterTypes::all();
        $res = [
            "printer"=>$prints,
            "types"=>$types
        ];
        return response()->json($res);

    }

    public function getPrinterStore(Request $request){

        $sid = $request->route('sid');
        $prints = Printer::with(['type'])->where('_store',$sid)->get();
        $types = PrinterTypes::all();
        $res = [
            "printer"=>$prints,
            "types"=>$types
        ];
        return response()->json($res);

    }

    public function testPrint(Request $request){
        $printer = $request->all();
        $testPrint = new MiniPrinterController($printer['ip_address'], $printer['_port'],5);
        $res =  $testPrint->testPrint();
        if($res){
            return response()->json('Impresion Exitosa', 200);
        }else{
            return response()->json('No se logro la conexion', 500);
        }

    }

    public function editPrint(Request $request){
        $printer = $request->all();
        if($printer['id']){
            $update = Printer::find($printer['id']);
            $update->name = $printer['name'];
            $update->_type =$printer['type']['id'];
            $update->ip_address = $printer['ip_address'];
            $update->save();
            $res = $update->load('type');
            if($res){
                return response()->json($res,200);
            }else{
                return response()->json('No se realizo la actualizacion',500);
            }
        }else{
            $print = new Printer;
            $print->name = $printer['name'];
            $print->_type =$printer['type']['id'];
            $print->ip_address = $printer['ip_address'];
            $print->_store = $printer['_store'];
            $print->save();
            $res = $print->load('type');
            if($res){
                return response()->json($res,200);
            }else{
                return response()->json('No se inserto',500);
            }
        }
    }

    public function deletePrint(Request $request){
        $printer = $request->id;
        $prnt = Printer::find($printer)->delete();
        if($prnt){
            return response()->json('Se elimino el dispositivo',200);
        }else{
            return response()->json('No se logro eliminar el dispositivo',500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

class MiniPrinterController extends Controller
{
    public $ip = null;
    public $port = null;
    public $printer = null;
    public $barcode_width = 2;
    public $barcode_height = 50;


    public function __construct($ip_printer, $port, $time = 15){
        try{
            $mp = explode(":",$ip_printer);
            $_ip = $mp[0];
            $_port = sizeof($mp)==1 ? 9100 : $mp[1];
            $connector = new NetworkPrintConnector($_ip, $_port, $time);
            $this->printer = new Printer($connector);
            $this->ip = $_ip;
            $this->port = $_port;
        }catch(\Exception $e){
            return $e;
        }
    }

    public function Order(){
        $printer = $this->printer;
        if(!$printer){
            return false;
        }
        $printer->text("Hola mundo"."\n");
        $printer->text("Hola mundo"."\n");
        $printer->text("Hola mundo"."\n");
        $printer->text("Hola mundo"."\n");
        $printer->text("Hola mundo"."\n");
        $printer->feed(1);
        $printer->cut();
        $printer->close();
        return true;
    }
    public function CliOrder($order,$status,$cash){
        $encabezado = $status == 2 ? 'Favor de escanear el pedido' : 'Favor de esperar su turno';
        $printer = $this->printer;
        if(!$printer){
            return false;
        }
        $sumary = $order->bodie->reduce(function($sumary, $product){
            $sumary['models'] = $sumary['models'] + 1;
            $sumary['units'] = $sumary['units'] + $product->amount_require;
            $sumary['total'] = $sumary['total'] + $product->total;
            return $sumary;
        }, ["models" => 0, "units"=>0, "total"=>0]);
        if($order->_order_by){
            $printer->setTextSize(2,2);
            $printer->setEmphasis(true);
            $printer->setReverseColors(true);
            $printer->setTextSize(2,2);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("ANEXO ".$order->_order_by." \n");
            $printer->setEmphasis(false);
            $printer->setReverseColors(false);
        }
        $printer->setTextSize(1,2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text($encabezado."\n");
        $printer->text("Gracias por su pedido ".$order->name.", te esperamos en"."\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(1,1);
        $printer->text("--------------------------------------------\n");
        $printer->setTextSize(1,2);
        $printer->setEmphasis(true);
        $printer->text("--".$cash->name."--" ." \n");
        $printer->setEmphasis(false);
        $printer->setTextSize(1,1);
        $printer->text("--------------------------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setTextSize(1,1);
        $printer->text(" Lo atendio: ".$order->user->name. " ".$order->user->surnames." \n");
        $printer->text(" Fecha/Hora: ".$order->created_at." \n");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setTextSize(1,1);
        $printer->text("Modelos: ");
        $printer->setTextSize(2,1);
        $printer->text($sumary['models']);
        $printer->setTextSize(1,1);
        $printer->text(" Piezas: ");
        $printer->setTextSize(2,1);
        $printer->text($sumary['units']."\n");
        // $printer->setTextSize(1,1);
        // $printer->text(" Total: $ ");
        // $printer->setTextSize(2,1);
        // $printer->text($sumary['total']."\n");
        $printer->setTextSize(1,1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("--------------------------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->qrCode($order->id,Printer::QR_ECLEVEL_L,10,Printer::QR_MODEL_1);
        $printer->feed(1);
        $printer->text($order->id."\n");
        $printer->text($order->store->name.", GRUPO VIZCARRA");
        $printer->feed(1);
        $printer->cut();
        $printer->close();
        return true;
        try{
        } catch(\Exception $e){
            return false;
        }
    }

    public function orderReceip(){

    }

}

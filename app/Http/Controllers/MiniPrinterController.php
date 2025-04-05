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

    public function CliOrder($order,$status,$cash){//Impresion de order para el cliente
        try{
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
        } catch(\Exception $e){
            return false;
        }
    }

    public function orderReceip($order,$status,$cash){// Impresion de preventa de la sucursal encabezado
        try{
        $printer = $this->printer;
        if(!$printer){
            throw new \Exception("No se encontró la impresora.");
        }
        $sumary = $order->bodie->reduce(function($sumary, $product){
            $sumary['models'] = $sumary['models'] + 1;
            $sumary['units'] = $sumary['units'] + $product->amount_require;
            $sumary['total'] = $sumary['total'] + $product->total;
            return $sumary;
        }, ["models" => 0, "units"=>0, "total"=>0]);
        $printer->setJustification(Printer::JUSTIFY_CENTER);

        if($order->printer>0){
            $printer->setTextSize(2,1);
            $printer->setReverseColors(true);
            $printer->text("REIMPRESION \n");
            $printer->setReverseColors(false);
        }

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
        $printer->text("Pedido para: \n");
        $printer->setTextSize(2,2);
        $printer->text($order->name." \n");
        // $printer->text("Pedido para:".$order->name." \n");
        $printer->setTextSize(1,1);
        $printer->text(" Vendedor: ".$order->user->name. " ".$order->user->surnames." \n");
        $printer->setTextSize(2,2);
        $printer->text("--  ".$cash->name."  --\n");
        $printer->setTextSize(1,1);
        $printer->text("----------------------------------------\n");
        $printer->text(" Fecha/Hora: ".$order->updated_at." \n");
        $printer->text("Modelos: ");
        $printer->setTextSize(2,1);
        $printer->text($sumary['models']);
        $printer->setTextSize(1,1);
        $printer->text(" Piezas: ");
        $printer->setTextSize(2,1);
        $printer->text(round($sumary['units'])."\n");
        $printer->setTextSize(1,1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("----------------------------------------\n");
        $y = 1;
        $products = $order->bodie->map(function($product){
            $product->product->locations->sortBy('path');
            return $product;
        })->sortBy(function($product){
            if(count($product->product->locations)>0){
                $location = $product->product->locations[0]->path;
                $res = '';
                $parts = explode('-', $location);
                foreach($parts as $part){
                    $numbers = preg_replace('/[^0-9]/', '', $part);
                    $letters = preg_replace('/[^a-zA-Z]/', '', $part);
                    if(strlen($numbers)==1){
                        $numbers = '0'.$numbers;
                    }
                    $res = $res.$letters.$numbers.'-';
                }
                return $res;
            }
            return '';
        })->groupBy(function($product){
            return $product->_supply_by;
        })->sortKeysDesc();
        $x = 1;
        foreach($products as $key => $el){
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setReverseColors(true);
            $printer->setTextSize(2,1);
            switch($key){
                case 1:
                    $printer->text(" Piezas - ".$x."/".count($products));
                    break;
                case 2:
                    $printer->text(" Docenas - ".$x."/".count($products));
                    break;
                case 3:
                    $printer->text(" Cajas - ".$x."/".count($products));
                    break;
            }
            $printer->setReverseColors(false);
            $printer->text(" P=>".$order->id."\n");
            foreach($el as $key => $product){
                $this->printBodyTicket($printer, $product, $key+1);
            }
            $printer->setTextSize(1,1);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $x++;
        }
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
        } catch(\Exception $e){
            return false;
        }
    }

    public function printBodyTicket($printer, $product, $y){ //Impresion de articulos para preventa en sucursal
        $locations = $product->product->locations->reduce(function($res, $location){
            return $res.$location->path.",";
        }, '');
        $stock =$product->product->stocks;
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setFont(Printer::FONT_B);
        $printer->setTextSize(3,1);
        $printer->text($y."█ ".trim($locations)."\n█ ");
        $printer->text($product->product->code." █ \n");
        $printer->setEmphasis(true);
        $printer->setTextSize(1,1);
        $printer->text($product->product->description."\n");
        $printer->setFont(Printer::FONT_A);
        switch($product->_supply_by){
            case 1:
                $printer->text("UNIDADES SOLICITADAS: ");
                $printer->setTextSize(2,1);
                break;
            case 2:
                $printer->text("DOCENAS SOLICITADAS: ");
                $printer->setTextSize(2,1);
                $printer->text($product->units.'x 12 = ');
                break;
            case 3:
                $printer->text("CAJAS SOLICITADAS: ");
                $units =   $product->amount_require / $product->units ;
                $printer->setTextSize(2,1);
                $printer->text($product->units."x".$units." = ");
                break;
        }
        $printer->setReverseColors(true);
        $printer->text(" ".$product->amount_require."pz"." \n");
        $printer->setReverseColors(false);
        $printer->setTextSize(1,1);
        $printer->text(" Stock=>".$stock[0]->_current." \n");
        if($product->notes){
            $printer->setTextSize(1,1);
            $printer->setReverseColors(true);
            $printer->text("Notas: ".$product->notes."\n");
            $printer->setReverseColors(false);
        }
        $printer->setEmphasis(false);
        $printer->feed(1);
    }

    public function testPrint(){
        try{
            $printer = $this->printer;
            if(!$printer){
                return false;
            }
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("--------------------------------------------\n");
            $printer->setTextSize(2,1);
            $printer->text("--PRUEBA DE IMPRESION--\n");
            $printer->setTextSize(1,1);
            $printer->text("--------------------------------------------\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("IP: ".$this->ip."\n");
            $printer->text("PORT: ".$this->port."\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->feed(1);
            $printer->text("GRUPO VIZCARRA\n");
            $printer->cut();
            $printer->close();
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

}

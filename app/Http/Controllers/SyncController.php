<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class SyncController extends Controller
{
    private $onstores = null;
    private $laps = [ "ini"=>null, "end"=>null ];
    private $flaps = [ "ini"=>null, "end"=>null ];

    public function __construct(Request $request){
        $this->onstores = $request->stores?:[];
        $this->laps["ini"] = CarbonImmutable::now();
        $this->laps["end"] = CarbonImmutable::now();

        $this->flaps["ini"] = CarbonImmutable::now()->sub(7,'days')->format('d/m/Y');
        $this->flaps["end"] = CarbonImmutable::now()->format('d/m/Y');
    }

    public function products(Request $request){
        /**
         * arreglo que retonara todo el resumen del algoritmo
         * fsol : almacena las filas crudas que seran enviadas a los factusoles de las tiendas
         * vizapp->ins : almacena las filas que seran insertadas
         * vizapp->upd : almacena las filas que seran actualizadas
         */
        $goals = [
            "fsol"=>[],
            "vizapp"=>[
                "ins"=>[],
                "upd"=>[]
            ]
        ];

        $fails = [];//almacen que retornara los errores por producto

        $fsol = new SsolController();//se llama la clase que conecta con ACCESS
        $updateds = $fsol->productsUpdated($this->flaps);// obtenemos los productos actualizados desde access con las fechas respectivas

        $fsolRawProducts = collect($updateds["products"])->toArray();// creamos una colleccion de la lista recuperada desde access
        $colsTabProds = array_keys($fsolRawProducts[0]);// obtenemos las columnas de la tabla de productos en access
        $fsolRawPrices = collect($updateds["prices"]);// obtenemos las precios relacionados a los productos recuperados

        // codificando filas...
        foreach($fsolRawProducts as $fsolrow){// recorremos el arreglo para codificar cada fila y poderla enviar
            foreach($colsTabProds as $col){ $fsolrow[$col] = utf8_encode($fsolrow[$col]); }

            //obtenemos la categoria del producto en factusol, para comprobar existencia en CentMYSQL
            $mcat = DB::table('product_categories as PC')
                ->join('product_categories as C','C.id','=','PC.root')
                ->select('PC.*')
                ->where([
                    ['PC.alias',$fsolrow['CP1ART']],
                    ['C.alias',$fsolrow['FAMART']]
                ])->first();

            $prices = $fsolRawPrices->filter(function($p) use($fsolrow){ return $fsolrow['CODART'] == $p['ARTLTA']; });
            $lprices = count($prices);

            if($mcat){// debe existir categoria en vizapp para pasarlo a vizapp, de lo contrario se agrega al almacen de errores
                if($lprices==7){// deben existir los 7 precios
                    $pmysql = DB::table('products')->where('code',$fsolrow['CODART'])->first();// se consulta si el producto ya esiste en CentMYSQL

                    $dimensions = explode('*', $fsolrow['DIMART']);// obtenemos las dimensiones del pack del producto
                    $_status = $fsolrow['NPUART'] == 0 ? 1 : 5;// obtenemos el status del producto en factusol para replicarlo en CentMYSQL

                    // construimos el objeto (fila) para insertar en CentMYSQL
                    $prod = [
                        "id"=>null,
                        "code" => utf8_encode($fsolrow['CODART']),
                        "name" => $fsolrow['CCOART'],
                        "barcode" => $fsolrow['EANART'],
                        "large" => utf8_encode($fsolrow['CP5ART']),
                        "description" => utf8_encode($fsolrow['DESART']),
                        "label" => utf8_encode($fsolrow['DEEART']),
                        "reference" => utf8_encode((string)$fsolrow['REFART']),
                        "created_at" => utf8_encode((string)$fsolrow['FALART']),
                        "updated_at" => utf8_encode((string)$fsolrow['FUMART']),
                        "cost" => $fsolrow['PCOART'],
                        "dimensions" =>json_encode([
                            "length" => count($dimensions)>0 ? $dimensions[0] : '',
                            "height" => count($dimensions)>1 ? $dimensions[1] : '',
                            "width" => count($dimensions)>2 ? $dimensions[2] : ''
                        ]),
                        "pieces" => explode(" ", $fsolrow['UPPART'])[0] ? intval(explode(" ", $fsolrow['UPPART'])[0]) : 0,
                        "_category" => $mcat->id,
                        // "_family" => utf8_encode($fsolrow['FAMART']),
                        "_status" => $_status,
                        "_provider" => intval($fsolrow['PHAART']),
                        "_unit" => 1
                    ];

                    if($pmysql){
                        $prod["id"]=$pmysql->id;
                        $goals["vizapp"]["upd"][] = ["art"=>$prod,"prices"=>$prices];
                    }else{
                        try {
                            $prod["id"]=DB::table('products')->insertGetId($prod);
                            // $prcs = $lconn->insert();
                            $goals["vizapp"]["ins"][] = ["art"=>$prod,"prices"=>$prices];
                        } catch (\Throwable $th) { $fails[] = $th->getMessage(); }
                    }
                }else{ $fails[] = "{$fsolrow['CODART']}: La longitud de los precios es ${$lprices}"; }
            }else{ $fails[] = "{$fsolrow['CODART']}: La categoria {$fsolrow['CP1ART']} de la familia {$fsolrow['FAMART']}, no se encuentra en VizApp"; }

            $goals["fsol"][] = $fsolrow;
        }

        return response()->json(["rangetime"=>$this->flaps,"fails"=>$fails,"rows"=>$goals ]);
    }
}

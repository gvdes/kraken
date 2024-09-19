<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StoresSeasons;
use Illuminate\Support\Facades\DB;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;

class ComparatorWarehouse extends Controller
{

    private $user=null; ##modelo del usuario que peticiona
    protected $uid=null; ##id del usuario que peticiona
    private $fixedsReq = null; ## parametros fijos del reques (userid rol etc)
    private $sid=null; ## id de la sucursal
    private $wid=null; ## id del almacen que peticiona
    private $store=null; ## modelo con la sucursal que peticiona
    private $warehouse=null; ## modelo con el almacen que peticiona
    private $seasons=[]; ## almacena los ids de las temporadas de la sucursal/almacen que peticiona
    ## almacena los tipos de reporte disponibles
    private $reports = [
        "A" => "rep_min_and_max",
        "B" => "rep_models_miss"
    ];

    public function __construct(Request $request){
        $this->sid = $request->route('sid');
        $this->wid = $request->route('wid');
        $this->store = Store::find($this->sid);
        $this->warehouse = Warehouse::find($this->wid);
    }

    public function index(){
        // no se esta usando por ahora
        return response()->json("no se esta usando por ahora");
    }

    public function report(Request $request){
        $report = $request->route('repid');
        $this->uid = $request->fixeds->uid;
        $this->user = User::find($this->uid);

        $this->seasons = $this->loadSeasons($this->sid); // temporadas de la sucursal

        $func = $this->reports[$report]; // reporte a generar
        $respReport = $this->$func();

        $resp = [
            "repid" => $report,
            "widReq" => $this->wid,
            "seasons" => $this->seasons,
            "store" => $this->store,
            "warehouse" => $this->warehouse,
            "user" => $this->user,
            "respReport"=>$respReport
        ];

        return response()->json($resp);
    }

    private function loadSeasons($sid){
        // obtenemos las temporadas de la tienda con su categoria
        $seasons = StoresSeasons::with([ "category" ])->where([ ["_store",$sid], ["_state",1] ])->get();

        // iteramos las temporadas para obtener las subcategorias de cada una
        $season_cats = $seasons->map(function($e) {
            $id = $e->_season; // id de la categoria raiz
            $children = DB::select('CALL categoriesOf(?)', [$id]); // subcategoriad de la categoria raiz
            return [ "parent"=>$e, "children"=>$children ];
        });

        // Creamos la lista completa de las categorias de la temporada
        $idsp = $season_cats->map(fn($sc) => $sc["parent"]->_season );// ids de las categorias padre
        $idsc = $season_cats->map(fn($sc) => $sc["children"])->flatten()->map(fn($c) => $c->id);// ids de las categorias hijas
        $ids_cats = $idsp->merge($idsc)->toArray(); // lista completa de ids de las categorias en las temporadas

        return [
            "cats" => $season_cats,
            "ids" => $ids_cats
        ];
    }

    private function rep_min_and_max(){
        $this->store->id;
        $this->store->_type;
        $isCds = ($this->store->_type == 1); ## indica si el request es desde una sucursal tipo CEDIS o no

        // 1.- Definir si la sucursal solicitantes es un CEDIS o SUCURSAL
        if ($isCds) {
            /**
             * LA PETICION FUE REALIZADA DESDE CEDIS
             * Se realizara el comparativo del almacen solicitante VS los demas almacenes
             * 1.1.- obtener los almacenes de CEDIS (sucursal 1) exceptuando el id del almacen que solicita
             */
            $wrhCompares = Warehouse::where([[ "_store",1 ], ["_type",1], ["id","!=",$this->wid]])->select("id")->get()->map(fn($r) => $r->id );
        }else{
            /**
             * LA PETICION FUE REALIZADA DESDE UNA SUCURSAL STANDARD.
             * Se realizara un reporte de minimos/maximos VS los almacenes de cedis
             * 1.1.- Obtener los ids de los almacenes CEDIS
             */
            $wrhCompares = Warehouse::where([[ "_store",1 ], ["_type",1] ])->select("id")->get()->map(fn($r) => $r->id );
        }

        // 2.- Obtener el stock de los productos del almacen solicitante
        /**
         * Obtenemos el stock actual del almacen que peticiona considerando las siguientes reglas:
         * Almacen solicitante:
         * ==> el producto debe estar activo: ["_state",1]
         * ==> el minimo debe estar definido: ["_min",">",0]
         * ==> el stock actual debe ser menor o igual al minimo: ["_current","<=","_min"]
         * ==> el estatus en el catalogo de roductos debe estar activo: ->whereHas("product", function($q){ $q->where("_state",1); })
         * */
        $stockWarehouse = ProductStock::with(["product"])->where([
            ["_state",1],
            ["_min",">",0],
            ["available","<=","_min"],
            ["_warehouse",$this->wid]
        ])->whereHas("product", function($q){ $q->where("_state",1); })->get();

        // 3.- Obtenemos lista de ids de los productos obtenidos por el almacen solicitante
        $idsProducts = $stockWarehouse->map(fn($r) => $r->_product);

        // 4.- Obtener los stocks de los ids recuperados de los productos en los almacenes por comparar
        $stockCompares = ProductStock::whereIn("_warehouse",$wrhCompares)->whereIn("_product",$idsProducts)->get();

        // 5.- Realizar el cruce para saber el "stock total real" de todos los almacenes y filtrar que productos se agregaran tentativamente al pedido de resurtido

        return [ "almacenes" => [$idsProducts, $stockCompares]];
        // return DB::select($query,$parameters);
    }

    private function rep_models_miss($store,$wrh,$ids_cats){
        return [];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAdditionalBarcode;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ProductFinder extends Controller
{
    public function __invoke(Request $request){
        $store = $request->route('sid'); // tienda sobre la que se obtendra el o los almacenes
        $key = $request->query('key'); // clave a buscar
        $wrh = json_decode($request->query('warehouse')); // define el/los almacen/es sobre los que se trabajara
        $withMedia = json_decode($request->query('media')); // define si incluiremos los precios del producto
        $withPrices = json_decode($request->query('prices')); // define si incluiremos los precios del producto
        $withLocations = json_decode($request->query('locations')); // define si incluiremos las ubicaciones sobre el/los almacen/es

        $onWrhs = $wrh ? [$wrh] : Warehouse::select("id")->where("_store",$store)->get();// definimos la lista de almacenes sobre los que se trabajara

        // busca coincidencias del producto a localizar entre el codigo y el codigo corto
            $items = Product::where( fn($q) => $q->where("code","LIKE","%$key%")->orWhere("short_code","LIKE","%$key%") )
                        ->with('state')
                        ->limit(100)
                        ->get();

        // si no hay coincidencias en codigos originales, buscara en codigos asociados
            if($items->count()<=0){
                $assocs = ProductAdditionalBarcode::where("additional_barcode","LIKE","%$key%")
                    ->select("_product")
                    ->limit(100)
                    ->get();
                $items = Product::whereIn("id",$assocs)->with('state')->get();
            }

        // carga codigos relacionados y stocks del almacen
            $items->load([ 'relateds', 'stocks' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs) ]);

        /**
         * Si se encuentran coincidencias,
         * se valida que elementos se cargaran
         * a los productos encontrados... (ubicaciones, etc...)
         */
            if(sizeof($items)){
                // agrega ubicaciones del almacen indicado
                if($withLocations){ $items->load([ 'locations' => fn($q) => $q->with("warehouse")->whereIn("_warehouse", $onWrhs) ]); };

                // agrega archivos multimedia del producto
                if($withMedia){ $items->load([ 'media' ]); }
            }

        return response()->json([
            "key" => $key,
            "items" => $items,
            "withLocations" => $withLocations,
            "withPrices" => $withPrices,
            "withMedia" => $withMedia,
            "onWarehouses" => $onWrhs,
        ]);
    }
}

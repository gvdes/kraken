<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\ProductStock;
use App\Models\WarehouseType;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request){
        $sid = $request->route('sid');
        $rol = $request->fixeds->rol;

        $constraints = [['_store',$sid]];

        if($rol == 16){// para vendedores, solo mostraremos almacen del tipo exhibicion
            array_push($constraints,['_type',2]);
        }elseif($rol == 12){// para almacenistas, solo mostraremos almacenes del tipo general
            array_push($constraints,['_type',1]);
        }

        $warehouses = Warehouse::with(['type'])->where($constraints)->get();
        $types = WarehouseType::all();

        return response()->json(["warehouses"=>$warehouses,"types"=>$types,"rol"=>$rol]);
    }

    public function open(Request $request){
        $wid = $request->route('wid');

        $warehouse = Warehouse::find($wid);

        return response()->json(["warehouse"=>$warehouse]);
    }

    public function structure(Request $request){
        $wid = $request->route('wid');

        $sections = Location::where([ ["root",0],["_warehouse",$wid] ])->get();
        return response()->json(["sections"=>$sections]);
    }

    public function sectionate(Request $request){
        $sibsSave = [];
        $wid = $request->route('wid');
        $siblings = $request->siblings;// cantidad de hermanos acrear
        $name = $request->name;
        $alias = $request->alias;
        $group = $request->group;
        $isnewgroup = $group?false:true; // indica si es un gupo nuevo
        $groupname = $isnewgroup ? "$name,$alias":$group["rawgroup"];// construimos el nombre del grupo
        $currentSiblings = Location::where([
            ["root", 0],
            ["_warehouse", $wid],
            ["_group", $groupname]
        ])->count();// traemos la cantidad de miembros actuales del grupo
        $resgroup = $isnewgroup ? "Se creara el grupo $groupname con $siblings miembros" : "Se integraran $siblings miembros al grupo $groupname";

        $x = $currentSiblings ? ($currentSiblings+1) : ($siblings>1 ? 1 : null);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            for ($i=1; $i<=$siblings ; $i++) {
                $row = new Location([
                    "name"      => "$name $x",
                    "alias"     => "$alias$x",
                    "path"      => "$alias$x",
                    "root"      => 0,
                    "deep"      => 0,
                    "_warehouse"=> $wid,
                    "_group"    => $groupname,
                ]);
                $row->save();
                $row->fresh();
                $sibsSave[] = $row;
                $x ? $x++ : null;
            }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json([
            "getted"=>$request->all(),
            "idwrh"=>$wid,
            "newgroup"=>$isnewgroup,
            "groupname"=>$groupname,
            "currentSiblings"=>$currentSiblings,
            "createds"=>$sibsSave,
            "resgroup"=>$resgroup,
        ]);
    }

    public function products(Request $request){
        $wid = $request->route('wid');

        $page = ProductStock::with([
            "product" => fn($q) => $q->with('relateds'),
            "state"
        ])->where("_warehouse", $wid)->paginate(50);

        return response()->json([ "page"=>$page ]);
    }

    public function resume(Request $request){
        $wid = $request->route('wid');

        $products = $this->productsIn($wid); // 0.- productos en el almacen
        $withStock = $this->productsIn($wid,1); // 1.- productos con stock
        $withStockMinMax = $this->productsIn($wid,2); // 2.- productos con Stock con Minimos y Maximos
        $withStockNoMinMax = $this->productsIn($wid,3); // 3.- productos con Stock sin Minimos y Maximos
        $withStockLocs = $this->productsIn($wid,4); // 4.- productos con stock y ubicados
        $withStockNoLocs = $this->productsIn($wid,5); // 5.- productos con stock y sin ubicar
        $forOutOfStock = $this->productsIn($wid,6); // 6.- productos por agotarse

        $noAvailables = $this->productsIn($wid,7); // 7.- productos en almacen no disponibles
        $withStockNoAvailables = $this->productsIn($wid,8); // 8.- productos con stock y no disponibles
        $withMinMaxNotAvailables = $this->productsIn($wid,9); // 9.- productos con minimos y maximos no disponibles

        $outOfStock = $this->productsIn($wid,20); // 10.-productos sin stock/negativos y ubicados
        $negativeStock = $this->productsIn($wid,21); // 11.- productos sin stock/negativos y ubicados
        $outOfStockLocs = $this->productsIn($wid,22); // 12.- productos sin stock/negativos y ubicados

        $reports = [
            $products,
            $withStock,
            $withStockMinMax,
            $withStockNoMinMax,
            $withStockLocs,
            $withStockNoLocs,
            $forOutOfStock,
            $noAvailables,
            $withStockNoAvailables,
            $withMinMaxNotAvailables,
            $outOfStock,
            $negativeStock,
            $outOfStockLocs
        ];

        return response()->json([ "reports"=>$reports ]);
    }

    public function report(Request $request){
        $wid = $request->route('wid');
        $rep = $request->route('repid');

        $report = $this->productsIn($wid,$rep,true);

        return response()->json($report);
    }

    public function create(){
        return response()->json("You try to create a warehouse");
    }

    private function productsIn($wid, $rep=0, $rows=false){
        $name = "Productos disponibles en almacen";

        switch($rep){

            case 1: // 1.- Productos disponibles en almacen con stock
                $rep = 1;
                $name = "Productos disponibles en almacen con stock";
                $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([ ["_warehouse",$wid],["_state",1],["_current",">",0] ]); }); break;

            case 2: // 2.- Productos disponibles en almacen con stock, con minimo y maximo
                $rep = 2;
                $name = "Productos disponibles en almacen, con stock, con minimo y maximo";
                $query = Product::whereHas("stocks", function($q) use($wid){
                            $q->where([ ["_warehouse",$wid],["_state",1],["_min",">",0],["_max",">",0], ["_current",">",0] ]);
                        });
                break;

            case 3: // 3.- Productos disponibles en almacen, con stock, sin minimo y/o maximo
                $rep = 3;
                $name = "Productos disponibles en almacen, con stock, sin minimo y/o maximo";
                $query = Product::whereHas("stocks", function($q) use($wid){
                            $q->where( fn($q) => $q->where("_min",0)->orWhere("_max",0) )->where([ ["_warehouse",$wid],["_state",1] ]);
                        });
                break;

            case 4: // 4.- Productos disponibles en almacen con stock y ubicados
                $rep = 4;
                $name = "Productos disponibles en almacen, con stock y ubicados";
                $query = Product::whereHas("stocks", function($q) use($wid){
                            $q->where([ ["_warehouse",$wid],["_state",1],["_current",">",0] ]);
                        })->whereHas("locations", function($q) use($wid){
                            $q->whereHas("warehouse", function($q) use($wid){ $q->where("id", $wid); });
                        });
                break;

            case 5: // 5.- Productos disponibles en almacen con stock y sin ubicar
                $rep = 5;
                $name = "Productos disponibles en almacen, con stock y sin ubicar";
                $query = Product::whereHas("stocks", function($q) use($wid){
                        $q->where([ ["_warehouse",$wid], ["_state",1], ["_current",">",0] ]);
                    })->whereDoesntHave("locations", function($q) use($wid){
                        $q->whereHas("warehouse", function($q) use($wid){ $q->where("id", $wid); });
                    });
                break;

            case 6: // 6.- Productos disponibles por agotarse
                $rep = 6;
                $name = "Productos disponibles en almacen, con stock por agotarse";
                $query = Product::whereHas("stocks", function($q) use($wid){
                        $q->whereRaw("(_current>0 and _current<_min)")->where([["_warehouse",$wid], ["_state",1]]);
                    });
                break;

            case 7: // 7.- Productos no disponibles en almacen
                $rep = 7;
                $name = "Productos no disponibles en almacen";
                $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([["_warehouse",$wid],["_state","!=",1]]); });
                break;

            case 8: // 8.- Productos no disponibles en almacen con stock
                $rep = 8;
                $name = "Productos no disponibles en almacen, con stock";
                $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([["_warehouse",$wid],["_current",">",0]], ["_state","!=",1]); });
                break;

            case 9: // 9.- Productos no disponibles en almacen con minimos y maximos
                $rep = 9;
                $name = "Productos no disponibles en almacen, con minimos y maximos";
                $query = Product::whereHas("stocks", function($q) use($wid){ $q->whereRaw("(_min>0 AND _max>0)")->where([["_warehouse",$wid],["_state","!=",1]]); });
                break;

            case 20: // 10.- Productos en almacen sin stock
                $rep = 20;
                $name = "Productos disponibles en almacen sin stock";
                $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([ ["_warehouse",$wid],["_state",1],["_current","=",0] ]); });
                break;

            case 21: // 11.- Productos en almacen en negativos
                $rep = 21;
                $name = "Productos en almacen en negativos";
                $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([ ["_warehouse",$wid],["_current","<",0] ]); });
                break;

            case 22: // 12.- Productos en almacen sin stock y ubicados
                $rep = 22;
                $name = "Productos en almacen sin stock y ubicados";
                $query = Product::whereHas("stocks", function($q) use($wid){
                        $q->where([ ["_warehouse",$wid],["_current","<",1] ]);
                    })->whereHas("locations", function($q) use($wid){
                        $q->whereHas("warehouse", function($q) use($wid){ $q->where("id", $wid); });
                    });
                break;

            // 0.- productos disponibles en almacen
            default: $query = Product::whereHas("stocks", function($q) use($wid){ $q->where([ ["_warehouse",$wid],["_state",1] ]); }); break;
        }

        if($rows){

            $data = $query->get()->load([
                "relateds",
                "unitsupply",
                "stock" => fn($q) => $q->where('_warehouse',$wid),
                "locations" => fn($q) => $q->where('_warehouse',$wid)
            ]);

        }else{ $data = $query->count(); }

        return [ "rep"=>$rep, "name"=>$name, "rows"=>$data ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductAdditionalBarcode;
use App\Models\Product;
use App\Models\Location;
use App\Models\ProductLocation;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LocatorController extends Controller
{
    public function __construct()
    {
        // validar el acceso a la ubicacion via Warehouse -> Store
    }

    public function location(Request $request){
        $loc = $request->route('loc');
        $sid = $request->route('sid');

        $location = Location::findOrFail($loc);
        $location->load(['warehouse' => fn($q) => $q->with('store')]);
        $wid = $location->warehouse->store->id;

        if ($wid == $sid){
            $idwrhs = Warehouse::where("_store",$wid)->select("id")->get();

            $location->load([
                'parent',
                'products' => fn($q) => $q->with([
                        'stocks' => fn($q) => $q->with([ 'warehouse' ])->whereIn("_warehouse", $idwrhs)
                    ])
                    ->where( "product_locations.deleted_at",null )
                    ->select('id','short_code','code','barcode','description'),
            ]);
            return response()->json($location);
        }else{ return response("No puedes usar esta ubicacion!",401); }
    }

    public function product(Request $request){
        $code = $request->route('code');
        $sid = $request->route('sid');

        $additional = null;
        $product = null;

        $idwrhs = Warehouse::where("_store",$sid)->select("id")->get();
        $additional = ProductAdditionalBarcode::with('product')->where("additional_barcode",$code)->first();

        if($additional){
            $product = $additional->product;
            $product->load([
                'relateds' => fn($q) => $q->with('product'),
                'locations' => fn($q) => $q->with(['warehouse'])
                    ->whereIn("_warehouse", $idwrhs)
                    ->where("product_locations.deleted_at",null),
                'stocks' => fn($q) => $q->with([ 'warehouse' ])->whereIn("_warehouse", $idwrhs)
            ]);
        }else{
            $product = Product::with([
                'relateds' => fn($q) => $q->with('product'),
                'locations' => fn($q) => $q->with('warehouse')
                    ->whereIn("_warehouse", $idwrhs)
                    ->where("product_locations.deleted_at",null),
                'stocks' => fn($q) => $q->with([ 'warehouse' ])->whereIn("_warehouse", $idwrhs)
            ])
            ->where("code",$code)
            ->orWhere("short_code",$code)
            ->orWhere("barcode",$code)
            ->first();
        }

        return response()->json([
            "target" => "Searching $code ...",
            "additional" => $additional,
            "product" => $product,
            "sid" => $sid
        ]);
    }

    public function toggle(Request $request){
        $sid = $request->route('sid');
        $loc = $request->loc;
        $pro = $request->pro;
        $link = null;
        $unlink = null;
        $action = $request->opt ? 'unlink' : 'link';
        $model = [ "_product"=>$pro, "_location"=>$loc ];

        $location = Location::findOrFail($loc);
        $location->load(['warehouse' => fn($q) => $q->with('store')]);

        $product = Product::where("id",$pro)->first();
        $wid = $location->warehouse->store->id;

        if($wid == $sid){

            if($action == 'link'){
                DB::table("product_locations")->where($model)->delete(); // borro todos los modelos actuales para evitar duplicidad
                $link = new ProductLocation($model);
                $link->save();
            }else{
                $now = Carbon::now();
                $unlink = DB::table("product_locations")->where($model)->update(["deleted_at"=>$now]);
            }

            $idwrhs = Warehouse::where("_store",$wid)->select("id")->get();

            $location->load([ 'products' => fn($q) => $q->with([
                    'stocks' => fn($q) => $q->with([ 'warehouse' ])->whereIn("_warehouse", $idwrhs)
                ])
                ->where("product_locations.deleted_at",null)
                ->select('id','short_code','code','barcode','description') ]);

            $product->load([ 'locations' => fn($q) => $q->with('warehouse')
                ->whereIn("_warehouse", $idwrhs)
                ->where("product_locations.deleted_at",null)
            ]);

        }else{ response("No puedes usar esta ubicacion!",401); }

        return response()->json([
            "location"=>$location,
            "product"=>$product,
            "action"=>$action,
            "link"=>$link,
            "unlink"=>$unlink
        ]);
    }
}

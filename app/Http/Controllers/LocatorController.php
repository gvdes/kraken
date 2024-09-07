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
            "target" => "Target code $code ...",
            "additional" => $additional,
            "product" => $product,
            "sid" => $sid
        ]);
    }

    public function toggle(Request $request){

        $now = Carbon::now();
        $sid = $request->route('sid');
        $lid = $request->lid;
        $pid = $request->pid;
        $model = [ "_product"=>$pid, "_location"=>$lid ];
        $link = null;
        $unlink = null;

        $idwrhs = Warehouse::where("_store",$sid)->select("id")->get();

        $proloc = ProductLocation::where($model)->first();

        if($proloc){ // vamo a eliminarla
            $unlink = ProductLocation::where($model)->delete();
        }else{ // vamo a crearla
            $link = new ProductLocation($model);
            $link->save();
            $link->load([
                "product" => fn($q) => $q->with(["stocks"]),
                "location" => fn($q) => $q->with(["warehouse"])->whereIn("_warehouse", $idwrhs)
            ]);
        }

        return response()->json([ "lid"=>$lid, "pid"=>$pid, "linked"=>$link, "unlinked"=>$unlink ]);
    }

    public function unlink(Request $request){
        $lid = $request->lid;
        $pid = $request->pid;
        $model = [ "_product"=>$pid, "_location"=>$lid ];
        $unlink = ProductLocation::where($model)->delete();
        return response()->json([ "lid"=>$lid, "pid"=>$pid, "unlinked"=>$unlink ]);
    }
}

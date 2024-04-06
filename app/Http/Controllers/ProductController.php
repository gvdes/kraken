<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStates;
use App\Models\ProductStock;
use App\Models\ProductCategory;
use App\Models\Provider;
use App\Models\UnitMeassure;
use App\Models\ProductAdditionalBarcode;




class ProductController extends Controller
{
    public function index(){
        $products = Product::with('state','category.familia.seccion','relateds','unitsupply','media','provider')->get();
        $states = ProductStates::all();
        $categorias = ProductCategory::all();
        $provider = Provider::all();
        $units= UnitMeassure::all();
        $res = [
            "products"=>$products,
            "states"=>$states,
            "categories"=>$categorias,
            "providers"=>$provider,
            "units"=>$units,
        ];
        return response()->json($res);
    }

    public function getProduct($product){
        $product = Product::with('state','category.familia.seccion','relateds','unitsupply','media','provider','Prices.rates','Prices.types')->where('id',$product)->first();
        return response()->json($product);
    }
}

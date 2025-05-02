<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductStates;
use App\Models\ProductStock;
use App\Models\ProductCategory;
use App\Models\Provider;
use App\Models\UnitMeassure;
use App\Models\Make;
use App\Models\ProductAdditionalBarcode;




class ProductController extends Controller
{
    public function index(){
        $states = ProductStates::all();
        $categorias = ProductCategory::all();
        $provider = Provider::all();
        $units= UnitMeassure::all();
        $makers = Make::all();
        $res = [
            // "products"=>$products,
            "states"=>$states,
            "categories"=>$categorias,
            "providers"=>$provider,
            "units"=>$units,
            "makers"=>$makers
        ];
        return response()->json($res);
    }

    public function searchProd(Request $request){
          $type = $request->Campo;
          $val = $request->val;
          $query = Product::with('state','category.familia.seccion','relateds','unitsupply','media','provider','Prices.rates','Prices.types','maker');
          if(in_array($type['id'],[1,2,3,4])){//CODIGO
            $query->where($type['name'],'like',"%".$val."%");
          }else{
            if($type['id'] == 5){//status
                $query->where('_state',$val['id']);
            }else if($type['id'] == 6){//seccion
                $query->whereHas('category.familia.seccion', function($q) use($val) { $q->where('id', $val['id']); });
            }else if($type['id'] == 7){//familia
                $query->whereHas('category.familia', function($q) use($val) { $q->where('id', $val['id']); });
            }else if($type['id'] == 8){//categoria
                $query->whereHas('category', function($q) use($val) { $q->where('id', $val['id']); });
            }
          }
          $products = $query->get();
          return response()->json($products);
    }

    public function getProduct($product){
        $product = Product::with('state','category.familia.seccion','relateds','unitsupply','media','provider','Prices.rates','Prices.types','maker')->where('id',$product)->first();
        return response()->json($product);
    }
}

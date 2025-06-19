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

    public function editProduct(Request $request){
        $id = isset($request->id);
        if($id){//edicion de producto
            return response()->json('existe');
        }else{//agregacion de producto
            $product = $request->all();
            $hasPrice = collect($prices)->contains(function ($item) {
                return $item['price'] > 0;
            });
            $codigoCorto = $this->genshortCode();
            $nwProduct = new Product;
            $nwProduct->short_code = $codigoCorto;
            $nwProduct->code = $product['code'];
            $nwProduct->barcode = $product['barcode'];
            $nwProduct->description = $product['description'];
            $nwProduct->label = strim($product['description'],100);
            $nwProduct->reference = $product['reference'];
            $nwProduct->pieces = $product['pieces'];
            $nwProduct->cost = $product['cost'];
            $nwProduct->default_amount = 1;
            $nwProduct->_provider = $product['provider']['id'];
            $nwProduct->_category = $product['category']['id'];
            $nwProduct->_maker = $product['maker']['id'];
            $nwProduct->_unit_mesure = 1;//revisar para ponerlo en el formulario
            $nwProduct->_state = $product['state']['id'];
            $nwProduct->_assortment_unit = 3;//revisar para ponerlo en el formulario
            $nwProduct->attributes = $product['attributes']['id'];//revisar para ponerlo en el formulario
            $nwProduct->save();
            $res = $nwProduct->fres();
            if($res){
                if($request->hasFile('product')){

                }
                if($product['relateds']){

                }

                if($hasPrice){

                }
            }else{
                return response()->json('Hubo un problema al crear el producto');
            }

            return response()->json($product);
        }
    }

    public function searchBarcode(Request $request){
        $barcode = $request->barcode;
        $product = Product::with('relateds')
        ->where('barcode',$barcode)
        ->orwhere('code',$barcode)
        ->orwhere('short_code',$barcode)
        ->orWhereHas('relateds', function($q) use($barcode) { $q->where('additional_barcode',$barcode);})
        ->get();
        return response()->json($product);
    }

    public function genBarcode(Request $request){
        $y = date('Y');
        $seccion = $request->secciones;
        $familia = $request->familias;
        $categoria = $request->categorias;
        do {
            $randomDigits = '';
            for ($i = 0; $i < 3; $i++) {
                $randomDigits .= mt_rand(0, 9);
            }
            $barcode = $y . $seccion['num'] . $familia['num'] . $categoria['num'] . $randomDigits;

            $exists = Product::where('barcode', $barcode)
                ->orWhere('code', $barcode)
                ->orwhere('short_code',$barcode)
                ->orWhereHas('relateds', function ($q) use ($barcode) {
                    $q->where('additional_barcode', $barcode);
                })
                ->exists();
        } while ($exists);
        return response()->json($barcode);
    }

    private function genshortCode(){
        do {
            $randomDigits = '';
            for ($i = 0; $i < 5; $i++) {
                $randomDigits .= mt_rand(0, 9);
            }
            $shortcode = $randomDigits;
            $exists = Product::where('short_code', $shortcode)
                ->orWhere('short_code', $shortcode)
                ->orWhereHas('relateds', function ($q) use ($shortcode) {
                    $q->where('additional_barcode', $shortcode);
                })
                ->exists();
        } while ($exists);
        return $shortcode;
    }
}

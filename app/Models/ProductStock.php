<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $table = 'product_stock';

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse','_warehouse','id');
    }

    public function product(){
        return $this->belongsTo("App\Models\Product","_product","id");
    }

    public function state(){
        return $this->hasOne('App\Models\ProductStates','id','_state');
    }

    public function unitsupply(){
        return $this->hasOneThrough('App\Models\UnitMeassure','App\Models\Product','id','id','_product','_assortment_unit');
    }
}

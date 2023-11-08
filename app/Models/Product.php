<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = "products";

    public function state(){ return $this->hasOne('App\Models\ProductStates','id','_state'); }

    public function relateds(){ return $this->hasMany('App\Models\ProductAdditionalBarcode','_product','id'); }

    public function locations(){ return $this->hasManyThrough('App\Models\Location','App\Models\ProductLocation','_product','id','id','_location'); }

    public function stocks(){ return $this->hasMany('App\Models\ProductStock','_product','id'); }

    public function stock(){ return $this->hasOne('App\Models\ProductStock','_product','id'); }

    public function unitsupply(){ return $this->hasOne('App\Models\UnitMeassure','id','_assortment_unit'); }

    public function media(){ return $this->hasMany('App\Models\ProductMedia','_product','id'); }

}

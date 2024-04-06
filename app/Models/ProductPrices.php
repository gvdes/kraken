<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrices extends Model
{
    protected $table = 'product_prices';

    public function rates(){ return $this->hasOne('App\Models\PricesRates','id','_rate'); }
    public function types(){ return $this->hasOne('App\Models\PricesTypes','id','_type'); }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderBodie extends Model
{

    protected $table = "order_bodies";
    public $timestamps = false;

    protected $fillable = ['_order','_product','amount_require','units','price','total','_rate','_state','notes','_supply_by'];

    public function product(){ return $this->belongsTo('\App\Models\Product','_product','id'); }

    public function unitsupply(){ return $this->hasOne('App\Models\UnitMeassure','id','_supply_by'); }

    public function rates(){ return $this->hasOne('App\Models\PricesRates','id','_rate'); }

}

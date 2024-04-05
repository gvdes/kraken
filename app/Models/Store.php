<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'stores';
    public $timestamps = false;


    public function storeSeasons(){ return $this->hasMany('App\Models\StoresSeasons','_store');}
    public function state(){ return $this->hasOne('App\Models\StoreStates','id', '_state');}
    public function type(){ return $this->hasOne('App\Models\StoreTypes','id', '_type');}
    public function price(){ return $this->hasOne('App\Models\PricesTypes','id', '_price_type');}
    public function users(){ return $this->hasMany('App\Models\User','_store');}
}

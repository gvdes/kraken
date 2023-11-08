<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';

    public function type(){
        return $this->hasOne('App\Models\WarehouseType','id','_type');
    }

    public function sections(){
        return $this->hasMany('App\Models\WarehouseSections','id','_warehouse');
    }

    public function store(){
        return $this->belongsTo('App\Models\Store','_store','id');
    }
}

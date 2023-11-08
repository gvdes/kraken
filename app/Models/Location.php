<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    protected $table = 'warehouse_locations';

    protected $fillable = ["name", "alias", "path", "root", "deep", "_warehouse", "_group" ];

    public function parent(){
        return $this->belongsTo('App\Models\Location','root','id');
    }

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse','_warehouse','id');
    }

    public function children(){
        return $this->hasMany('App\Models\Location','id','root');
    }

    public function products(){
        return $this->hasManyThrough('App\Models\Product','App\Models\ProductLocation','_location','id','id','_product');
    }
}

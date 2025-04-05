<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TPV extends Model
{
    protected $table = "point_of_sales";
    public $timestamps = false;


    // public function warehouse(){ return $this->hasOne('App\Models\Warehouse','_warehouse');}
    public function warehouse(){return $this->belongsTo('\App\Models\Warehouse','_warehouse','id'); }
    public function client(){return $this->belongsTo('\App\Models\Client','_client','id'); }
    // public function client(){ return $this->hasOne('App\Models\Client','_client');}

}

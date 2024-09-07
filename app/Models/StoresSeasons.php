<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoresSeasons extends Model
{
    protected $table = 'store_seasons';
    public $timestamps = false;

    public function category(){
        return $this->belongsTo('\App\Models\ProductCategory','_season','id');
    }

    public function store(){
        return $this->belongsTo('\App\Models\Store','_store','id');
    }
}

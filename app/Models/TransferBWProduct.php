<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferBWProduct extends Model
{
    use HasFactory;
    protected $table = "transfer_bw_bodies";

    public function product(){
        return $this->belongsTo('\App\Models\Product','_product','id');
    }

    public function addby(){
        return $this->belongsTo('\App\Models\User','_user','id');
    }
}

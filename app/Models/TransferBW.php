<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferBW extends Model
{
    use HasFactory;
    protected $table = "transfers_between_warehouses";

    public function from(){ // almacen de origen
        return $this->belongsTo('App\Models\Warehouse',"_origin_warehouse");
    }

    public function to(){ // almacen destino
        return $this->belongsTo('App\Models\Warehouse',"_destini_warehouse");
    }

    public function created_by(){ // almacen destino
        return $this->hasOne('App\Models\User',"id","_user");
    }

    public function state(){ // status de la transferencia
        return $this->hasOne('App\Models\TransferStates',"id","_state");
    }
}

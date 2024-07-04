<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStateConfig extends Model
{
    protected $table = "order_states_configs";
    public $timestamps = false;

    public function state(){
        return $this->belongsTo('\App\Models\OrderState','_state_order','id');
    }
}

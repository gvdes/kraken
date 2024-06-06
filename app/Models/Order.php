<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";

    public function user(){ return $this->belongsTo('\App\Models\User','_created_by','id'); }
    public function state(){ return $this->belongsTo('\App\Models\OrderState','_state','id'); }
    public function client(){return $this->belongsTo('\App\Models\Client','_client','id'); }
    public function bodie(){return $this->hasMany('\App\Models\OrderBodie','_order','id'); }

}

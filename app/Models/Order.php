<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";

    public function user(){ return $this->belongsTo('\App\Models\User','_created_by','id'); }
    public function store(){ return $this->belongsTo('\App\Models\Store','_store','id'); }
    public function state(){ return $this->belongsTo('\App\Models\OrderState','_state','id'); }
    public function client(){return $this->belongsTo('\App\Models\Client','_client','id'); }
    public function order(){return $this->belongsTo(Order::class,'_order_by','id'); }
    public function bodie(){return $this->hasMany('\App\Models\OrderBodie','_order','id'); }
    public function cash(){return $this->belongsTo('\App\Models\CashRegister','_cash','id'); }

    public function products(){
        return $this->belongsToMany('\App\Models\Product', 'order_bodies', '_order', '_product')
            ->using(OrderBodie::class)
            ->withPivot('_order','_assorted_by','_product','amount_require','price','total','_rate','_state','notes','_order_by','deleted_at','_added_by','_supply_by','units');
    }


}

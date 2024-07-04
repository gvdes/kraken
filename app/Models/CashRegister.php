<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $table = "cash_registers";
    public $timestamps = false;

    public function store(){ return $this->belongsTo('\App\Models\Store','_store','id'); }
    public function state(){ return $this->belongsTo('\App\Models\CashState','_state','id'); }
    public function cashier(){ return $this->belongsTo('\App\Models\CashCashier','id','_cash'); }

}

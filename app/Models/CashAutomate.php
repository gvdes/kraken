<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAutomate extends Model
{
    protected $table = "cash_atomate";
    public $timestamps = false;



    public function user(){ return $this->belongsTo('\App\Models\User','_cashier','id'); }
    public function printer(){ return $this->belongsTo('\App\Models\Printer','_printer','id'); }
    public function cash(){ return $this->belongsTo('\App\Models\CashRegister','_cash','id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashCashier extends Model
{
    protected $table = "cash_cashiers";
    public $timestamps = false;
    protected $fillable = [
        '_cashier',
        '_cash',
        '_printer_tck',
        '_printer_order',
        'created_at',
        'id_tpv',
        'initial_cash',
        'start_time',
    ];
    protected $primaryKey = null; // Indica que no hay clave primaria única
    public $incrementing = false; // Desactiva la auto-incrementación

    public function user(){ return $this->belongsTo('\App\Models\User','_cashier','id'); }
    public function cash(){ return $this->belongsTo('\App\Models\CashRegister','_cash','id'); }
    public function printer(){ return $this->belongsTo('\App\Models\Printer','_printer_tck','id'); }
    public function printer_order(){ return $this->belongsTo('\App\Models\Printer','_printer_order','id'); }



}

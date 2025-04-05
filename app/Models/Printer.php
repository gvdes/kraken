<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $table = "printers";

    public function store(){ return $this->hasOne('App\Models\Store','id','_store'); }

    public function type(){ return $this->hasOne('App\Models\PrinterTypes','id','_type'); }
}

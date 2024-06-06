<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = "clients";

    public function rate(){return $this->belongsTo('\App\Models\PricesRates','_rate','id'); }

}

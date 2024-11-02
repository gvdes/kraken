<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestockBody extends Model
{
    use HasFactory;

    protected $table = 'requisition_bodies';

    public function product(){ return $this->hasOne("App\Models\Product","id","_product"); }
}

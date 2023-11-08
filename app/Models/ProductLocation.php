<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLocation extends Model
{
    protected $table = 'product_locations';
    protected $fillable = ["_product", "_location" ];

    public function warehouse(){
        return $this->hasOneThrough("App\Models\Warehouse","App\Models\Location","id","id","_location","_warehouse");
    }
}

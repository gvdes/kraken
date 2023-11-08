<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseSections extends Model
{
    use HasFactory;

    protected $table = 'warehouse_locations';

    public function warehouse(){
        $this->belongsTo('App\Models\Warehouse','_warehouse','id');
    }
}

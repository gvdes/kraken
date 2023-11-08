<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAdditionalBarcode extends Model
{
    use HasFactory;

    protected $table = 'product_additionals_barcodes';

    public function product(){
        return $this->belongsTo('App\Models\Product','_product','id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;
    protected $table = 'product_categories';

    public function category(){
        return $this->belongsTo('\App\Modles\ProductCategory');
    }
    public function products()
    {
        return $this->hasMany('App\Models\Product', '_category');
    }

    public function familia()
    {
        return $this->belongsTo(ProductCategory::class, 'root');
    }

    public function seccion()
    {
        return $this->belongsTo(ProductCategory::class, 'root');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seasons extends Model
{
    protected $table = 'seasons';

    public function category(){
        return $this->belongsTo('\App\Models\ProductCategory','_category','id');
    }
}

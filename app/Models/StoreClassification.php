<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreClassification extends Model
{
    protected $table = 'store_classifications';
    public $timestamps = false;

    public function store(){ return $this->hasOne('App\Models\Store','id', '_store');}
    public function clasification(){ return $this->hasOne('App\Models\ClassificationStore','id','_classification_store');}


}

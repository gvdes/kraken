<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserClassification extends Model
{
    protected $table = 'user_classifications';
    public $timestamps = false;

    public function store(){ return $this->hasOne('App\Models\StoreClassification','id', '_store_classification');}
    public function classification(){ return $this->hasOne('App\Models\Classification','id', '_classification');}
    public function user(){ return $this->hasOne('App\Models\User','_user', 'id');}
}

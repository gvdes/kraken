<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'user_areas';

    public function roles(){ return $this->hasMany('App\Models\UserRol','_area','id'); }

}

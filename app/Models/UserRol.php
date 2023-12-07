<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRol extends Model
{
    use HasFactory;

    protected $table = 'user_roles';

    public function area(){ return $this->hasOne('App\Models\Area','id','_area');}

    public function permissions(){ return $this->hasMany('App\Models\RolDefaultPermission','_rol','id');}

}

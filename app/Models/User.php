<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    // use HasFactory;

    protected $table = 'users';
    protected $hidden = [ 'password', 'nip' ];

    public function store(){ return $this->hasOne('App\Models\Store','id','_store'); }

    public function stores(){ return $this->belongsToMany('App\Models\Store','user_stores','_user','_store'); }

    public function modules(){ return $this->hasMany('App\Models\UserModules','_user'); }

    // public function permissions(){ return $this->hasMany('App\Models\UserPermissions','_user'); }

    public function state(){ return $this->hasOne('App\Models\UserStates','id','_state'); }

    public function rol(){ return $this->hasOne('App\Models\UserRol','id','_rol'); }
}

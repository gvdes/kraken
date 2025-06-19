<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    protected $table = "commitments";
    public $timestamps = false;


    public function staff(){
        return $this->hasOne('App\Models\User',"id","_staff");
    }

    public function created_by(){
        return $this->hasOne('App\Models\User',"id","_created_by");
    }

    public function manager(){
        return $this->hasOne('App\Models\User',"id","_manager");
    }

    public function admin(){
        return $this->hasOne('App\Models\User',"id","_admin");
    }
}

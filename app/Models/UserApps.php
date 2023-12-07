<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserApps extends Model
{
    // use HasFactory;
    protected $table = 'user_apps';

    public function app(){ return $this->hasOne('App\Models\Apps','id','_app'); }
}

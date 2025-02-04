<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turn extends Model
{
    protected $table = 'assist_turns';
    protected $fillable = ['_week','_user','hour_hand','_year'];
    public $timestamps = false;

    public function users(){ return $this->hasOne('App\Models\User','id','_user');}
}

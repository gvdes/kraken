<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferBWUsers extends Model
{
    protected $table = "user_transfers";
    public $timestamps = false;

    public function account(){
        return $this->hasOne('App\Models\User','id','_user');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanctionUser extends Model
{
    protected $table = "sanction_user";
    public $timestamps = false;

    public function sanction(){ return $this->hasOne('App\Models\Sanction','id','_sanction'); }
}

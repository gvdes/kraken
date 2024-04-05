<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'providers';
    public $timestamps = false;
    public function type(){ return $this->hasOne('App\Models\ProviderType','id', '_type');}
}

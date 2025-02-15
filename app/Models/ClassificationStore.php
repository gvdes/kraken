<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificationStore extends Model
{
    protected $table = 'classification_store';

    public function bonuses(){ return $this->hasMany('App\Models\StoreBonuses','_classification_store', 'id');}
}

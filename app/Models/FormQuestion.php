<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormQuestion extends Model
{
    protected $table = "form_questions";

    public function type(){return $this->belongsTo('\App\Models\QuestionType','_type','id'); }
    public function options(){ return $this->hasMany('App\Models\QuestionOption','_question');}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionResponse extends Model
{
    protected $table = "question_responses";
    public $timestamps = false;

    public function question(){return $this->hasOne('\App\Models\FormQuestion','id','_question'); }
    // public function selectedOption() {
    //     return $this->belongsTo('\App\Models\QuestionOption', '_option'); //quitar si hay problema
    // }
}

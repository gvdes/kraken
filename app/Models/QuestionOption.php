<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    protected $fillable = ["_question",'option','condition'];
    protected $table = "question_options";
}

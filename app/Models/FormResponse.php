<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $table = "form_responses";
    public function responses(){return $this->hasMany('\App\Models\QuestionResponse','_response','id'); }
}

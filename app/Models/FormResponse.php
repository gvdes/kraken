<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $table = "form_responses";
    public function responses(){return $this->hasMany('\App\Models\QuestionResponse','_response','id'); }
    public function store(){return $this->hasOne('\App\Models\Store','id','_store'); }
    public function user(){return $this->hasOne('\App\Models\User','id','_user'); }
    public function form(){return $this->hasOne('\App\Models\Form','id','_form'); }




}

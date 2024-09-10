<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $table = "forms";

    public function type(){return $this->belongsTo('\App\Models\FormType','_type','id'); }
    public function responsible(){return $this->belongsTo('\App\Models\FormResponsible','_responsible','id'); }
    public function user(){return $this->belongsTo('\App\Models\FormResponsible','_created_by','id'); }
    public function question(){return $this->hasMany('\App\Models\FormQuestion','_form','id'); }

}

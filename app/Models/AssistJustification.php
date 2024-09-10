<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistJustification extends Model
{
    protected $table = "assist_justifications";
    public $timestamps = false;

    public function user(){ return $this->belongsTo('\App\Models\User','_user','id'); }
    public function type(){ return $this->belongsTo('\App\Models\JustificationType','_type','id'); }
    public function state(){ return $this->belongsTo('\App\Models\JustificationState','_state','id'); }
    public function paymen(){ return $this->belongsTo('\App\Models\PaymenPercentage','_pay_percentage','id'); }


}

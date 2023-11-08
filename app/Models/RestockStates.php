<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestockStates extends Model
{
    use HasFactory;
    protected $table = 'requisition_states';
}

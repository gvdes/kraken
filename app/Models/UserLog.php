<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    protected $table = "user_logs";
    protected $fillable = [ "_user", "_type_log", "details" ];
    public $timestamps = false;
}

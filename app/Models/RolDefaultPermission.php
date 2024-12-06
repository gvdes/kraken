<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolDefaultPermission extends Model
{
    protected $table = 'role_default_permissions';
    public $timestamps = false;
    // public $incrementing = false;
    // protected $primaryKey = null;

    protected $fillable = ['_rol','_permission','_module'];
}

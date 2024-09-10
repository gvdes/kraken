<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleApp extends Model
{
    use HasFactory;

    protected $table = 'modules_app';
    protected $keyType = 'string';

    public function modules(){
        return $this->hasMany('App\Modules\ModuleApp','id','root');
    }

    public function parent()
    {
        return $this->belongsTo(ModuleApp::class, 'root', 'id');
    }

    // Relación para obtener los hijos
    public function children()
    {
        return $this->hasMany(ModuleApp::class, 'root', 'id');
    }
}

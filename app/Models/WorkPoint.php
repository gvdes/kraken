<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * ALGUN DIA, CUANDO YA ESTE TRABAJANDO SOBRE LA NUEVA APP
 * ESTE MODELO SERA ELIMINADO PUES SOLO SE UTILIZO PARA VER
 * LO QUE ESTA EJECUTANDO EL QUERY ACTUAL DE RESURTIDOS
 *
 */

class WorkPoint extends Model{

    protected $table = 'workpoints';
    protected $fillable = ['fullname', 'alias', 'dominio', 'active', '_type', '_client'];
    protected $hidden = ['_type'];
    protected $dateFormat = 'U';
    public $timestamps = false;

    public function type(){
        return $this->belongsTo('App\Models\WorkPointType', '_type');
    }

    public function accounts_base(){
        return $this->hasMany('App\Models\User', '_wp_principal', 'id');
    }

    public function accounts(){
        return $this->belongsToMany('App\Models\User', 'account_workpoints', '_workpoint', '_account')
                    ->using('App\Models\Account')
                    ->withPivot(['_status', '_rol', 'id']);
    }

    public function orders(){
        return $this->hasMany('App\Models\Order', '_workpoint', 'id');
    }

    public function cyclecounts(){
        return $this->hasMany('App\Models\CycleCount', '_workpoint', 'id');
    }

    /**
     * RELATIONSHIPS WITH REQUISITION'S MODELS
     */
    public function supplied(){
        return $this->hasMany('App\Models\Requisition','_workpoint_to', 'id');
    }

    public function to_supply(){
        return $this->hasMany('App\Models\Requisition','_workpoint_from', 'id');
    }

    /**
     * RELATIONSHIPS WITH CELLER'S MODELS
     */
    public function products(){
        return $this->belongsToMany('App\Models\Product', 'product_stock', '_workpoint', '_product')
                    ->withPivot('min', 'max', 'stock', 'gen', 'exh', '_status');
    }

    public function printers(){
        return $this->hasMany('App\Models\Printer', '_workpoint');
    }

    /**
     * RELATIONSHIPS WITH VENTAS MODELS
     */
    public function cash(){
        return $this->hasMany('App\Models\CashRegister', '_workpoint');
    }

    public function wildrawals(){
        return $this->hasMany('App\Models\Wildrawals', '_workpoint');
    }
}

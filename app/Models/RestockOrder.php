<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestockOrder extends Model
{
    use HasFactory;

    protected $table = 'requisitions';

    protected $fillable = [ "num_ticket", "num_ticket_store", "_created_by", "warehouse_from", "warehouse_to", "_type", "_state", "printed" ];

    public function owner(){ return $this->belongsTo("App\Models\User","_created_by","id"); }

    public function type(){ return $this->hasOne("App\Models\RestockTypes","id","_type"); }

    public function state(){ return $this->hasOne("App\Models\RestockStates","id","_state"); }

    public function originWrh(){ return $this->belongsTo("App\Models\Warehouse","warehouse_from","id"); }

    public function sourceWrh(){ return $this->belongsTo("App\Models\Warehouse","warehouse_to","id"); }

    public function log(){ return []; }

    public function products(){ return $this->hasMany("App\Models\RestockBody","_requisition","id"); }
}

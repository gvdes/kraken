<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestockOrder extends Model
{
    use HasFactory;

    protected $table = 'requisitions';

    protected $fillable = [ "num_ticket", "num_ticket_store", "_created_by", "_store_from", "_store_to", "_type", "_state", "printed" ];

    public function owner(){ return $this->belongsTo("App\Models\User","_created_by","id"); }

    public function fromStore(){ return $this->belongsTo("App\Models\Store","_store_from","id"); }

    public function toStore(){ return $this->belongsTo("App\Models\Store","_store_to","id"); }

    public function type(){ return $this->hasOne("App\Models\RestockTypes","id","_type"); }

    public function state(){ return $this->hasOne("App\Models\RestockStates","id","_state"); }

    public function log(){

    }
}

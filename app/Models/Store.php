<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'stores';

    protected $hidden = [ 'access_file', 'port', 'local_port', 'local_domain', 'domain' ];
}

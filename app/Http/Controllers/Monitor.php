<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Monitor extends Controller
{
    public function index(){
        $stores = Store::all();
        return response()->json(["stores"=>$stores]);
    }
}

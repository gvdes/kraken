<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Provider;

class ProvidersController extends Controller
{
    public function getProviders(Request $request){
        $providers = Provider::all();
        return response()->json($providers,200);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    private $sid = null;
    public function __construct(Request $request)
    {
        $this->sid = $request->route('sid');
    }

    public function index(Request $request){

        return response()->json([
            "msg" => "Welcome at the store ($this->sid)",
        ]);
    }
}

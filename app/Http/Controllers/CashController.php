<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashRegister;
use App\Models\CashState;
use App\Models\User;
use App\Models\Printer;



class CashController extends Controller
{
    public function getCash(Request $request){
        $store = $request->route('sid');
        $cashier = USER::where('_store',$store)->whereIn('_rol',[13,14])->get();
        $cash = CashRegister::with([
            'state',
            'cashier' => fn($q) => $q->with('user','printer')->whereDate("created_at", date('Y-m-d'))
            ])->where('_store',$store)->get();
        $states = CashState::get();
        $printers = Printer::where([['_store',$store],['_type',1]])->get();
        $res = [
            "cashier"=>$cashier,
            "cash"=>$cash,
            "state"=>$states,
            "printers"=>$printers,
            "date"=>date('Y-m-d'),
        ];
        return response()->json($res,200);
    }
}

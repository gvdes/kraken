<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function fullReset(Request $request){

        $_accounts = $request->accounts;
        $log = [];

        if(gettype($_accounts)=="array" && sizeof($_accounts)>0){

            $accounts = User::whereIn("id",$_accounts)->orWhereIn("nick",$_accounts)->get();

            foreach($accounts as $acc){
                try {

                    $unsetLogs = DB::table('user_logs')->where("_user",$acc->id)->delete();
                    $reset = DB::table('users')->where("id",$acc->id)->update(["change_password"=>1, "_state"=>1]);
                    $log[] = [ "account"=>"{$acc->id}::{$acc->nick}", "logremove"=>$unsetLogs, "reset"=>$reset ];

                } catch (\Throwable $th) { $log[] = [ "error"=>$th->getMessage() ]; }
            }

            return response()->json($log);
        }else{
            return response("Meriyein: asegurate de enviar un array valido y no vacio", 400);
        }
    }
}

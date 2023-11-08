<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\CarbonImmutable;

class SsolController extends Controller
{
    private $conn = null;
    private $laps = [ "ini"=>null, "end"=>null ];

    public function __construct(){
        $access = env("ACCDB_FILE");

        if(file_exists($access)){
            try{
                $this->conn = new \PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb, *.accdb)};charset=UTF-8; DBQ=".$access."; Uid=; Pwd=;");
            }catch(\PDOException $e){ die($e->getMessage()); }
        }else{ die("$access no es un origen de datos valido."); }
    }

    public function productsUpdated($laps){
        $ini = $laps["ini"];
        $end = $laps["end"];

        try{
            $qprods = "SELECT * FROM F_ART WHERE FUMART Between #".$ini."# AND #".$end."#";
            $exec = $this->conn->prepare($qprods);
            $exec->execute();
            $products = $exec->fetchAll(\PDO::FETCH_ASSOC);

            $qprices = "SELECT
                PL.PRELTA,
                PL.TARLTA ,
                PL.ARTLTA
                FROM F_LTA AS PL
                INNER JOIN F_ART AS ART ON PL.ARTLTA=ART.CODART
                WHERE ART.FUMART Between #".$ini."# AND #".$end."#";
            $exec = $this->conn->prepare($qprices);
            $exec->execute();
            $prices = $exec->fetchAll(\PDO::FETCH_ASSOC);

            return ["products" => $products, "prices"=>$prices];

        }catch (\PDOException $e){ die($e->getMessage()); }
    }
}

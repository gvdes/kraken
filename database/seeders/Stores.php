<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\StoreTypes;
use App\Models\StoreStates;
use App\Models\PricesTypes;
use App\Models\Seasons;




class Stores extends Seeder
{

    public function run()
    {
        $instyp = [
            ["id"=>1,"name"=>"Cedis"],
            ["id"=>2,"name"=>"Sucursal"],
        ];
        $type = StoreTypes::insert($instyp);
        echo $type;

        $instate = [
            ["id"=>1,"name"=>"On"],
            ["id"=>2,"name"=>"Off"],
        ];
        $state = StoreStates::insert($instate);


        $insprices = [
            ["id"=>1,"name"=>"Local"],
            ["id"=>2,"name"=>"Foraneo"],
        ];
        $prices = Pricestypes::insert($insprices);

        $store = new Store();
        $store->id = 1;
        $store->name = 'CEDIS';
        $store->alias = 'CDS';
        $store->domain = 'www.csp.com';
        $store->port = 22120;
        $store->local_domain = '192.168.10.53';
        $store->local_port = 1920;
        $store->_state = 1;
        $store->_type = 1;
        $store->_price_type = 1;
        $store->access_file = 'VPA2024';
        $store->save();

    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StoresSeasons;
use App\Models\ProductStock;

class massminmax extends Seeder
{
    /**
     * Para poblar masivamente minimos y maximos de una tienda
     *
     * @return void
     */
    public function run()
    {

        $sid = 3;// id de la tienda a llenar (necesario para obtener categorias de esa sucursal)
        $wid = 10;// id del almacen a rellenar (relacionado a la sucursal)

        $seasons = StoresSeasons::with([ "category" ])->where([ ["_store",$sid], ["_state",1] ])->get();

        // iteramos las temporadas para obtener las subcategorias de cada una
        $season_cats = $seasons->map(function($e) {
            $id = $e->_season; // id de la categoria raiz
            $children = DB::select('CALL categoriesOf(?)', [$id]); // subcategoriad de la categoria raiz
            return [ "parent"=>$e, "children"=>$children ];
        });

        $names_parents = $season_cats->map(fn($sc) => $sc["parent"]->name );// ids de las categorias padre
        $names_childs = $season_cats->map(fn($sc) => $sc["children"])->flatten()->map(fn($c) => $c->name);// ids de las categorias hijas
        $names_cats = $names_parents->merge($names_childs);
        $str_names = $names_cats->implode(",");

        // Creamos la lista completa de las categorias de la temporada
        $idsp = $season_cats->map(fn($sc) => $sc["parent"]->_season );// ids de las categorias padre
        $idsc = $season_cats->map(fn($sc) => $sc["children"])->flatten()->map(fn($c) => $c->id);// ids de las categorias hijas
        $ids_cats = $idsp->merge($idsc)->toArray(); // lista completa de ids de las categorias en las temporadas
        $str_ids = implode(",",$ids_cats);

        $categoryPlaceholders = implode(',', array_fill(0, count($ids_cats), '?'));
        $parameters = array_merge([$wid], $ids_cats);

        $query = 'UPDATE product_stock ps
                    INNER JOIN products p ON p.id = ps.`_product`
                SET
                    ps.`_min` = 15, ps.`_max` = 25
                WHERE
                    ps.`_warehouse` = ? AND
                    ps.`_state` = 1 AND
                    ps.`_min` = 0 AND ps.`_max` = 0 AND
                    p.`_category` IN ('.$categoryPlaceholders.')';

        $resp = DB::update($query,$parameters);

        echo("Filas actualizadas:" . $resp . "\n");
        var_dump($str_ids);
        var_dump($str_names);
    }
}

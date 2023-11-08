<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Location;

class LocationController extends Controller
{

    private function fullpath($lid){
        $predecesors = [];

        $location = Location::find($lid);

        $predecesors[] = $location;

        $deep = $location->deep;
        $root = $location->root;
        $nextid = $root;

        if($nextid>0){
            for ($i=$deep; $i>0 ; $i--) {
                $parent = Location::where("id",$nextid)->first();
                $predecesors[] = $parent;
                $nextid = $parent->root;
            }
        }

        return $predecesors;
    }

    public function open(Request $request){
        $wid = $request->route('wid');
        $lid = $request->route('lid');

        $warehouse = Warehouse::find($wid);
        $location = Location::find($lid);
        $fullpath = $this->fullpath($lid);

        return response()->json([ "warehouse"=>$warehouse, "location"=>$location, "fullpath"=>$fullpath]);
    }

    public function structure(Request $request){
        $lid = $request->route('lid');
        $sections = Location::where("root",$lid)->get();

        return response()->json(["sections" => $sections]);
    }

    public function sectionate(Request $request){
        $sibsSave = [];
        $wid = $request->route('wid');
        $lid = $request->route('lid');
        $siblings = $request->siblings;// cantidad de hermanos acrear
        $name = $request->name;
        $alias = $request->alias;
        $group = $request->group;
        $deep = ($request->deep+1);
        $root = $request->root;
        $isnewgroup = $group ? false:true; // indica si es un gupo nuevo
        $groupname = $isnewgroup ? "$name,$alias":$group["rawgroup"];// construimos el nombre del grupo
        $resgroup = $isnewgroup ? "Se creara el grupo $groupname con $siblings miembros" : "Se integraran $siblings miembros al grupo $groupname";
        $fullpath = collect($this->fullpath($lid))->reverse()->implode("alias",'-');

        $currentSiblings = Location::where([
            ["root", $root],
            ["_warehouse", $wid],
            ["_group", $groupname]
        ])->count();// traemos la cantidad de miembros actuales del grupo

        $x = $currentSiblings ? ($currentSiblings+1) : ($siblings>1 ? 1 : null);

        for ($i=1; $i<=$siblings; $i++) {
            $row = new Location([
                "name"      => "$name $x",
                "alias"     => "$alias$x",
                "path"      => "$fullpath-$alias$x",
                "root"      => $root,
                "deep"      => $deep,
                "_warehouse"=> $wid,
                "_group"    => $groupname,
            ]);
            $row->save();
            $row->fresh();
            $sibsSave[] = $row;
            $x ? $x++ : null;
        }

        return response()->json([
            "getted"=>$request->all(),
            "idwrh"=>$wid,
            "newgroup"=>$isnewgroup,
            "groupname"=>$groupname,
            "currentSiblings"=>$currentSiblings,
            "createds"=>$sibsSave,
            "resgroup"=>$resgroup,
            "fullpath"=>$fullpath
        ]);
    }

    public function products(Request $request){
        return response()->json();
    }

    public function resume(){
        return response()->json("Hello from Location Resume");
    }
}

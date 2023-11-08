<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductMedia;
use Carbon\Carbon;

class VmediaController extends Controller
{
    public function addImages(Request $request){

        try {
            $product = $request->query("product");
            $filescatchs = $request->file();
            $filessize = sizeof($filescatchs);
            $serverpath = env('MMEDIA_PATH');

            $saveds = [];

            foreach ($filescatchs as $file) {
                $storedAs = $file->store(null,"multimedia"); // nombre del archivo, Disco deseado
                $name = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();

                $media = new ProductMedia();
                $media->path = $storedAs;
                $media->_product = $product;
                $media->type_file = $ext;
                $media->name = $name;

                if($media->save()){ $saveds[]=$media; }
            }

            return response()->json([
                "product"=>$product,
                "serverpath"=>$serverpath,
                "filescatches"=>$filessize,
                "stored"=>$saveds,
            ]);
        } catch (\Throwable $th) { return response()->json($th->getMessage(),500); }
    }

    public function archive(Request $request){
        $media = $request->file;
        $idfile = $media["id"];
        $now = Carbon::now()->format("Y-m-d H:i:s");

        $filedb = ProductMedia::find($idfile);
        $filedb->deleted_at = $now;
        $filedb->save();

        return response()->json([ "done"=>true, "media"=>$filedb, "deleted_at"=>$now ]);
    }

    // public function clearArchive(){

    // }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = "document_types";
    protected $fillable = ["name", "serie" ];
    public $timestamps = false;
}

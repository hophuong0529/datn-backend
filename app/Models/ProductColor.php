<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    protected $table = 'productcolor';

    protected $fillable = [
        'product_id',
        'color_id',
        'quantity',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;
}

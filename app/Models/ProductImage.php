<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductImage extends Model
{
    protected $table = 'productimage';

    public function product()
    {
        return $this->belongTo(Product::class, 'product_id', 'id');
    }

    protected $fillable = [
        'path',
        'product_id',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;
}

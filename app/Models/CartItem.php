<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cartitem';

    protected $fillable = [
        'product_id',
        'cart_id',
        'color_id',
        'quantity',
        'price',
        'price_sale',
        'total_item',
        'product'
    ];

    public $timestamps = true;

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function color()
    {
        return $this->hasOne(Color::class, 'id', 'color_id');
    }
}

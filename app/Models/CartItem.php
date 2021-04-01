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
        'priceSale'
    ];

    public $timestamps = true;
}

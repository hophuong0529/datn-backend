<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';

    public $timestamps = true;



    protected $fillable = [
        'code',
        'name',
        'producer_id',
        'description',
        'subcategory_id',
        'discount',
        'price_import',
        'price',
        'quantity',
        'is_top',
    ];

    public function sub()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class,);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function colors()
    {
        return $this->belongstoMany(Color::class, 'productcolor');
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }
}

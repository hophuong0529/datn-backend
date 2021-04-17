<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';

    public $timestamps = true;

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function colors()
    {
        return $this->belongstoMany(Color::class, 'productcolor');
    }

    protected $fillable = [
        'code',
        'name',
        'producer_id',
        'description',
        'subcategory_id',
        'discount',
        'price',
        'quantity',
        'is_top',
    ];

    public function sub()
    {
        return $this->hasOne(SubCategory::class, 'id', 'subcategory_id');
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class, 'producer_id', 'id');
    }
}

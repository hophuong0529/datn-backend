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
        'subcategory_id',
        'discount',
        'price',
        'is_top',
        'created_at',
        'updated_at',
    ];

    public function sub()
    {
        return $this->hasOne(SubCategory::class, 'id', 'subcategory_id');
    }
}

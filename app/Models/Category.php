<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';

    public $timestamps = false;

    public function subs()
    {
        return $this->hasMany(SubCategory::class, 'category_id', 'id');
    }
}

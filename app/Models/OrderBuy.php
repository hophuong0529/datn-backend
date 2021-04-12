<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBuy extends Model
{
    protected $table = 'orderbuy';

    protected $fillable = [
        'user_id',
        'status_id',
        'quantity',
        ];

    public $timestamps = true;

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    public function status()
    {
        return $this->hasOne(StatusOrder::class, 'id', 'status_id');
    }
}

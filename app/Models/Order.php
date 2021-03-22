<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'status_id',
        'quantity',
        'created_at',
        'updated_at'
        ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    public function status_order()
    {
        return $this->hasOne(StatusOrder::class, 'id', 'status_id');
    }
}

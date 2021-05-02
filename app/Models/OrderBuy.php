<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mockery\Generator\Method;

class OrderBuy extends Model
{
    protected $table = 'orderbuy';

    protected $fillable = [
        'user_id',
        'status_id',
        'quantity',
        'created_at'
        ];

    public $timestamps = true;

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function method()
    {
        return $this->belongsTo(OrderMethod::class);
    }

    public function receiver()
    {
        return $this->hasOne(Receiver::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

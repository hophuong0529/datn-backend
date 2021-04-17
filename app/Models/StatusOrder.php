<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOrder extends Model
{
    protected $table = 'statusorder';

    protected $fillable = [
        'status'
    ];
}

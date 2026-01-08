<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id',
        'sku',
        'upload_file',
        'status',
        'scanned_at',
        'printed_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'printed_at' => 'datetime',
    ];
}

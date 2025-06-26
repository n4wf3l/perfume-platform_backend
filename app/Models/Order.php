<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'city', 'postal_code',
        'paypal_order_id', 'total', 'status'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

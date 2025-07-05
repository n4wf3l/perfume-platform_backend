<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductImage;


class Product extends Model
{
    protected $fillable = ['name', 'description', 'price', 'stock', 'size_ml', 'category_id', 'is_hero', 'is_flagship','olfactive_notes',
    'gender'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

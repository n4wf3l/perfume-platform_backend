<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'order'];

     protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

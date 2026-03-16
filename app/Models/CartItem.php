<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant() {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    
}

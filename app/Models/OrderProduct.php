<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OrderProduct extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'discount_price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function variant() {
        return $this->belongsTo(ProductVariant::class, 'id');
    }
}

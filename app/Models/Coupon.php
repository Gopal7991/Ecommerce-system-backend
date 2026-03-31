<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'coupon_type',
        'brand_id',
        'discount_percentage',
        'start_date',
        'end_date',
        'min_order_amount',
        'max_discount',
        'max_attach',
        'used_count',
        'is_active'
    ];

}

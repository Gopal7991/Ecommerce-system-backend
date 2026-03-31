<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'coupon_id',
        'session_id',
        'user_id',
        'email',
        'address',
        'city',
        'state',
        'zipcode',
        'phone',
        'amount',
        'status'
    ];
   
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
    public function productVariant()
    {
        return $this->hasMany(productVariant::class, 'pr');
    }

}

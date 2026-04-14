<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

use App\Observers\ProductVariantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([ProductVariantObserver::class])]

class ProductVariant extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'product_id',
        'size',
        'color',
        'quantity',
        'is_active'
    ];

    protected function color(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucfirst(strtolower($value)), // Stores as "Red"
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

     protected $fillable = [
        'name',
        'parent_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Get direct children
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parentRecursive()
    {
        return $this->parent()->with('parentRecursive');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'name', 'description', 'image_path',
        'base_fare', 'price_per_km', 'price_per_min', 'min_price',
        'capacity', 'luggage', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_fare' => 'float',
            'price_per_km' => 'float',
            'price_per_min' => 'float',
            'min_price' => 'float',
            'capacity' => 'integer',
            'luggage' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}

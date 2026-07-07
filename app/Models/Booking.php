<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'booking_ref', 'customer_id', 'vehicle_id', 'vehicle_name',
        'full_name', 'email', 'phone',
        'pickup_address', 'dropoff_address',
        'pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng',
        'ride_date', 'ride_time',
        'distance_km', 'duration_min', 'price', 'currency',
        'customer_message', 'payment_method', 'payment_status',
        'transaction_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'ride_date' => 'date:Y-m-d',
            'distance_km' => 'float',
            'duration_min' => 'float',
            'price' => 'float',
            'pickup_lat' => 'float',
            'pickup_lng' => 'float',
            'dropoff_lat' => 'float',
            'dropoff_lng' => 'float',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}

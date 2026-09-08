<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'capacity', 
        'location'
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'user_id',
        'start_time',
        'end_time',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope validasi booking yang tumpang tindih (overlap).
     * Menggunakan konsep half-open interval [start, end) agar boundary tidak dianggap bentrok.
     */
    public function scopeOverlapping(Builder $query, int $roomId, string $startTime, string $endTime): Builder
    {
        return $query->where('room_id', $roomId)
                     ->where('status', 'confirmed') 
                     ->where('start_time', '<', $endTime)
                     ->where('end_time', '>', $startTime);
    }
    public function logs()
    {
        return $this->hasMany(BookingLog::class);
    }
}
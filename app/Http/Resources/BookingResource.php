<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'user_id' => $this->user_id,
            // Konversi dari acuan database ke zona waktu lokal 
            'start_time' => Carbon::parse($this->start_time, 'UTC')
                                   ->setTimezone('Asia/Jakarta')
                                   ->toDateTimeString(),
            'end_time' => Carbon::parse($this->end_time, 'UTC')
                                 ->setTimezone('Asia/Jakarta')
                                 ->toDateTimeString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
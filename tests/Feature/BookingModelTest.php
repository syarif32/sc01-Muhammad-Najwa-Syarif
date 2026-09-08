<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_overlapping_works_without_http()
    {
        // 1. Data (Tanpa HTTP Request)
        $user = User::factory()->create();
        $room = Room::create(['name' => 'Model Room', 'capacity' => 10, 'location' => 'L1']);
        
        // Buat booking 
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'confirmed'
        ]);

        //   2. Cek Scope Overlapping
        
        // Skenario A: Bentrok (10:30 - 11:30)
        $isConflict = Booking::overlapping($room->id, '2026-10-10 10:30:00', '2026-10-10 11:30:00')->exists();
        $this->assertTrue($isConflict, 'Harusnya mendeteksi overlap tanpa perlu HTTP API');

        // Skenario B: Aman (11:00 - 12:00, boundary)
        $isSafe = !Booking::overlapping($room->id, '2026-10-10 11:00:00', '2026-10-10 12:00:00')->exists();
        $this->assertTrue($isSafe, 'Harusnya aman, boundary time tidak dihitung overlap');

        // Skenario C: Aman (Status cancelled tidak memblokir)
        Booking::first()->update(['status' => 'cancelled']); // Batalkan jadwal pertama
        $isSafeNow = !Booking::overlapping($room->id, '2026-10-10 10:00:00', '2026-10-10 11:00:00')->exists();
        $this->assertTrue($isSafeNow, 'Booking yang di-cancel harusnya diabaikan oleh scope');
    }
}
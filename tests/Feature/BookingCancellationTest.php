<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_cancel_their_booking()
    {
        $owner = User::factory()->create();
        $room = Room::create(['name' => 'Alpha', 'capacity' => 10, 'location' => 'L1']);
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'confirmed'
        ]);
        $response = $this->withHeaders(['X-User-Id' => $owner->id])
                         ->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_non_owner_cannot_cancel_booking()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create(); 
        $room = Room::create(['name' => 'Alpha', 'capacity' => 10, 'location' => 'L1']);
        
        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $owner->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'confirmed'
        ]);

        
        $response = $this->withHeaders(['X-User-Id' => $stranger->id])
                         ->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403); // Forbidden
    }
}
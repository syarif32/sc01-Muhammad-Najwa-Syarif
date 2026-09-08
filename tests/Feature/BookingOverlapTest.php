<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingOverlapTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->room = Room::create(['name' => 'Alpha', 'capacity' => 10, 'location' => 'L1']);
    }

    public function test_can_create_valid_booking()
    {
        $response = $this->withHeaders(['X-User-Id' => $this->user->id])
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'start_time' => '2026-10-10 10:00:00',
                'end_time' => '2026-10-10 11:00:00',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bookings', ['status' => 'confirmed']);
    }

    public function test_rejects_exact_overlap()
    {
        // Buat booking awal 
        Booking::create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'confirmed'
        ]);

        // Coba booking di jam yang sama 
        $response = $this->withHeaders(['X-User-Id' => $this->user->id])
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'start_time' => '2026-10-10 10:00:00',
                'end_time' => '2026-10-10 11:00:00',
            ]);

        $response->assertStatus(409); // Conflict
    }

    public function test_rejects_partial_overlap()
    {
        Booking::create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'confirmed'
        ]);

     
        $response = $this->withHeaders(['X-User-Id' => $this->user->id])
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'start_time' => '2026-10-10 10:30:00',
                'end_time' => '2026-10-10 11:30:00',
            ]);

        $response->assertStatus(409);
    }

    public function test_accepts_boundary_booking()
    {
        Booking::create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'confirmed'
        ]);

        $response = $this->withHeaders(['X-User-Id' => $this->user->id])
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'start_time' => '2026-10-10 11:00:00',
                'end_time' => '2026-10-10 12:00:00',
            ]);

        $response->assertStatus(201);
    }

    public function test_cancelled_booking_does_not_block_new_booking()
    {
        //  CANCELLED
        Booking::create([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'start_time' => '2026-10-10 10:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'cancelled' 
        ]);
        $response = $this->withHeaders(['X-User-Id' => $this->user->id])
            ->postJson('/api/bookings', [
                'room_id' => $this->room->id,
                'start_time' => '2026-10-10 10:00:00',
                'end_time' => '2026-10-10 11:00:00',
            ]);

        $response->assertStatus(201);
    }
}
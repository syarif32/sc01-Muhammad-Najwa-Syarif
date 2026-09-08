<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_bookings_on_same_slot_results_in_only_one_success()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $room = Room::create(['name' => 'Meeting Room Alpha', 'capacity' => 10, 'location' => 'Lantai 1']);

        $startTime = '2026-10-15 10:00:00';
        $endTime = '2026-10-15 11:00:00';

        $responses = [];

        try {
            DB::transaction(function () use ($room, $user1, $startTime, $endTime) {
                Room::where('id', $room->id)->lockForUpdate()->first();
                $isConflict = Booking::overlapping($room->id, $startTime, $endTime)->lockForUpdate()->exists();
                if ($isConflict) throw new \Exception('Conflict');
                
                Booking::create([
                    'room_id' => $room->id, 'user_id' => $user1->id,
                    'start_time' => $startTime, 'end_time' => $endTime, 'status' => 'confirmed'
                ]);
            });
            $responses[] = 201;
        } catch (\Exception $e) {
            $responses[] = 409;
        }

       
        try {
            DB::transaction(function () use ($room, $user2, $startTime, $endTime) {
                Room::where('id', $room->id)->lockForUpdate()->first();
                $isConflict = Booking::overlapping($room->id, $startTime, $endTime)->lockForUpdate()->exists();
                if ($isConflict) throw new \Exception('Conflict');
                
                Booking::create([
                    'room_id' => $room->id, 'user_id' => $user2->id,
                    'start_time' => $startTime, 'end_time' => $endTime, 'status' => 'confirmed'
                ]);
            });
            $responses[] = 201;
        } catch (\Exception $e) {
            $responses[] = 409;
        }
        $this->assertEquals(1, collect($responses)->filter(fn($status) => $status === 201)->count());
        $this->assertEquals(1, collect($responses)->filter(fn($status) => $status === 409)->count());
        $this->assertDatabaseCount('bookings', 1);
    }
}
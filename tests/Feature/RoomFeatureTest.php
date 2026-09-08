<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Room;

class RoomFeatureTest extends TestCase
{
    use RefreshDatabase; // mereset database ketika setiap kali test dijalankan

    public function test_can_create_room_successfully()
    {
        $response = $this->postJson('/api/rooms', [
            'name' => 'Ruang Meeting A',
            'capacity' => 10,
            'location' => 'Lantai 1'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Ruang Meeting A');
                 
        $this->assertDatabaseHas('rooms', ['name' => 'Ruang Meeting A']);
    }

    public function test_cannot_create_invalid_room()
    {
       // mencoba data yang tidak valid atau tidak komplit 
        $response = $this->postJson('/api/rooms', [
            'capacity' => 10,
            'location' => 'Lantai 1'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }
}
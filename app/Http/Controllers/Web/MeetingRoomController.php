<?php

namespace App\Http\Controllers\Web;

use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MeetingRoomController extends Controller
{
    // Menampilkan halaman utama 
    public function index(Request $request)
    {
        $rooms = Room::all();
        $selectedRoom = null;
        $bookings = [];
        $date = $request->input('date', date('Y-m-d'));

        if ($request->has('room_id')) {
            $selectedRoom = Room::find($request->input('room_id'));
            if ($selectedRoom) {
                $bookings = Booking::where('room_id', $selectedRoom->id)
                    ->whereDate('start_time', $date)
                    ->orderBy('start_time')
                    ->get();
            }
        }

        return view('meeting.index', compact('rooms', 'selectedRoom', 'bookings', 'date'));
    }

    // Menyimpan Ruangan Baru
    public function storeRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
        ]);

        Room::create($request->all());

        return redirect()->route('meeting.index')->with('success', 'Ruangan baru berhasil ditambahkan!');
    }

    // Menghapus Ruangan
    public function destroyRoom(Room $room)
    {
        $room->delete();
        return redirect()->route('meeting.index')->with('success', 'Ruangan berhasil dihapus.');
    }

    // 2. Membuat Booking dengan Aturan Overlap & Transaction
    public function storeBooking(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'user_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Room::where('id', $request->room_id)->lockForUpdate()->firstOrFail();

                $isConflict = Booking::overlapping(
                    $request->room_id,
                    $request->start_time,
                    $request->end_time
                )->lockForUpdate()->exists();

                if ($isConflict) {
                    throw new \Exception('Jadwal bentrok! Ruangan sudah dibooking pada rentang waktu tersebut.');
                }

                Booking::create([
                    'room_id' => $request->room_id,
                    'user_id' => $request->user_id,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'status' => 'confirmed',
                ]);
            });

            return redirect()->route('meeting.index', [
                'room_id' => $request->room_id, 
                'date' => date('Y-m-d', strtotime($request->start_time))
            ])->with('success', 'Reservasi berhasil dibuat!');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // Membatalkan Booking 
    public function cancelBooking(Request $request, Booking $booking)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        // Cek Authorization: Apakah user_id yang memasukkan request adalah pemilik booking?
        if ((int)$booking->user_id !== (int)$request->user_id) {
            return back()->with('error', 'Aksi ditolak: Anda bukan pemilik (owner) dari reservasi ini.');
        }

        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Reservasi ini sudah berstatus dibatalkan.');
        }

        $booking->status = 'cancelled';
        $booking->save();

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }
}
<?php

namespace App\Http\Controllers\Api;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Requests\StoreBookingRequest;
class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Room $room)
    {
        
        $request->validate([
            'date' => 'required|date',
            'status' => 'sometimes|in:confirmed,cancelled',
        ]);

        $date = $request->query('date');
        $status = $request->query('status');
        $bookings = Booking::where('room_id', $room->id)
            ->when($date, fn($q) => $q->whereDate('start_time', $date))
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderBy('start_time')
            ->get();

        return BookingResource::collection($bookings);
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();
        $user = $request->input('resolved_user');

        try {
            $booking = DB::transaction(function () use ($validated, $user) {
                
                //  Gatekeeper lock baris room supaya request lain untuk room yang sama antre
                Room::where('id', $validated['room_id'])->lockForUpdate()->firstOrFail();

                // cek overlap menggunakan locking read 
                $isConflict = Booking::overlapping(
                    $validated['room_id'], 
                    $validated['start_time'], 
                    $validated['end_time']
                )->lockForUpdate()->exists();

                if ($isConflict) {
                    abort(response()->json([
                        'message' => 'The requested room is already booked for this time slot.'
                    ], 409));
                }

                // booking baru
                return Booking::create([
                    'room_id' => $validated['room_id'],
                    'user_id' => $user->id,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'status' => 'confirmed',
                ]);
            });

            return (new BookingResource($booking))
                ->response()
                ->setStatusCode(201);

        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    
    public function destroy(Booking $booking)
    {
        // Authorization
        \Illuminate\Support\Facades\Gate::authorize('cancel', $booking);

        //  Cek status saat ini
        if ($booking->status === 'cancelled') {
            return response()->json([
                'message' => 'This booking is already cancelled.'
            ], 409);
        }

        // Ubah status Soft-cancel
        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'data' => clone new BookingResource($booking)
        ], 200);
    }
}

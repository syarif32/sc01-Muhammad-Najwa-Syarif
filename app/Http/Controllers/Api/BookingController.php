<?php

namespace App\Http\Controllers\Api;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Requests\StoreBookingRequest;
use Carbon\Carbon;
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
        $userId = Auth::id();

        try {
            $booking = DB::transaction(function () use ($validated, $userId) { 
                
                // Gatekeeper lock baris room
                Room::where('id', $validated['room_id'])->lockForUpdate()->firstOrFail();

                // Cek overlap menggunakan locking read 
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

                // Booking baru
                return Booking::create([
                    'room_id' => $validated['room_id'],
                    'user_id' => $userId, 
                    'start_time' => Carbon::parse($validated['start_time'], 'Asia/Jakarta')->setTimezone('UTC')->toDateTimeString(),
                    'end_time' => Carbon::parse($validated['end_time'], 'Asia/Jakarta')->setTimezone('UTC')->toDateTimeString(),
                    
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
    public function searchAvailable(Request $request)
{
    $request->validate([
        'start_time' => 'required|date',
        'end_time' => 'required|date|after:start_time',
        'min_capacity' => 'nullable|integer|min:1',
    ]);
    $startTime = $request->input('start_time');
    $endTime = $request->input('end_time');
    $minCapacity = $request->input('min_capacity', 1);
    $bookedRoomIds = Booking::where('status', 'confirmed')
        ->where(function ($query) use ($startTime, $endTime) {
            $query->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
        })
        ->pluck('room_id');
    $availableRooms = Room::where('capacity', '>=', $minCapacity)
        ->whereNotIn('id', $bookedRoomIds)
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $availableRooms
    ]);
}
}

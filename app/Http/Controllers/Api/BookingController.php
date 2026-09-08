<?php

namespace App\Http\Controllers\Api;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Requests\StoreBookingRequest;
class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;

class RoomController extends Controller
{
    public function index()
    {
        return RoomResource::collection(Room::all());
    }
    // store
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        return (new RoomResource($room))
            ->response()
            ->setStatusCode(201);
    }

    // show
    public function show(Room $room)
    {
        return new RoomResource($room);
    }
    // update
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update($request->validated());

        return new RoomResource($room);
    }
    // destroy
    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json(null, 204);
    }
}
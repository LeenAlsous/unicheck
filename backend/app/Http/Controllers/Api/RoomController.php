<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lecture rooms, for picking where a session takes place.
 */
class RoomController extends Controller
{
    /**
     * All rooms.
     */
    public function index(): AnonymousResourceCollection
    {
        return RoomResource::collection(Room::orderBy('building')->orderBy('name')->get());
    }
}

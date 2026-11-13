<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;

class RoomController extends Controller
{
    /**
     * All rooms, to choose where a lecture takes place.
     */
    public function index()
    {
        return Room::orderBy('building')->orderBy('name')->get();
    }
}

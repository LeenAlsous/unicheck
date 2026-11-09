<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;

/**
 * Students checking in by scanning the QR code.
 */
class CheckInController extends Controller
{
    /**
     * Check in.
     *
     * Send the scanned QR text with the phone's current location. Returns 201 with the new
     * attendance record, or 200 with the existing one if the student already checked in.
     * On a 422 response, show `message` to the student: it says what went wrong
     * (expired code, not in the room, check-in closed, ...).
     */
    public function store(CheckInRequest $request, CheckInService $checkIns): JsonResponse
    {
        $record = $checkIns->checkIn(
            student: $request->user(),
            code: $request->string('code')->toString(),
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
            accuracyMeters: $request->float('accuracy_meters'),
            isMocked: $request->boolean('is_mocked'),
        );

        return (new AttendanceRecordResource($record))
            ->response()
            ->setStatusCode($record->wasRecentlyCreated ? 201 : 200);
    }
}

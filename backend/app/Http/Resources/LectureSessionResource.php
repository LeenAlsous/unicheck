<?php

namespace App\Http\Resources;

use App\Models\LectureSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A lecture session. Only the instructor's responses include the QR secret.
 *
 * @mixin LectureSession
 */
class LectureSessionResource extends JsonResource
{
    private bool $withQrSecret = false;

    /**
     * Include `qr_secret` and `server_time_ms`, so the instructor app can draw the rotating QR.
     */
    public function withQrSecret(): static
    {
        $this->withQrSecret = true;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'room' => new RoomResource($this->whenLoaded('room')),
            'status' => $this->status,
            'opened_at' => $this->opened_at,
            'closes_at' => $this->closes_at,
            'closed_at' => $this->closed_at,
            'check_in_open' => $this->isCheckInOpen(),
            'qr_step_seconds' => $this->qr_step_seconds,
            'present_count' => $this->whenCounted('present'),
            'absent_count' => $this->whenCounted('absent'),
            $this->mergeWhen($this->withQrSecret, fn () => [
                /** Key for making the rotating QR codes. Never show it or send it to students. */
                'qr_secret' => $this->qr_secret,
                /** Server clock in Unix milliseconds; the app uses it to correct its own clock. */
                'server_time_ms' => now()->getTimestampMs(),
            ]),
        ];
    }
}

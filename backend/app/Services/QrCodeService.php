<?php

namespace App\Services;

use App\Models\LectureSession;

/**
 * Rotating QR codes for a lecture session.
 *
 * The code changes every `qr_step_seconds` (10 by default). The instructor app makes the codes
 * itself with the same recipe, so the server only has to check them:
 *
 *     step = floor(unix_seconds / qr_step_seconds)
 *     hash = first 8 characters of sha256(qr_secret + step), in hex
 *     code = "{session_id}-{step}-{hash}"          e.g. "42-179421840-1f0c2a9b"
 *
 * A code works during its own step and the next one, so a screenshot sent to a friend
 * stops working after 10–20 seconds.
 */
class QrCodeService
{
    /**
     * Which 10-second window we are in right now. Goes up by 1 every step.
     */
    public function currentStep(LectureSession $session): int
    {
        return intdiv(now()->timestamp, $session->qr_step_seconds);
    }

    /**
     * The QR code for one step.
     */
    public function codeFor(LectureSession $session, int $step): string
    {
        $hash = substr(hash('sha256', $session->qr_secret.$step), 0, 8);

        return $session->id.'-'.$step.'-'.$hash;
    }

    /**
     * The session ID at the start of a scanned code, or null if it isn't one of our codes.
     */
    public function sessionIdFrom(string $code): ?int
    {
        $parts = explode('-', $code);

        if (count($parts) !== 3 || ! ctype_digit($parts[0])) {
            return null;
        }

        return (int) $parts[0];
    }

    /**
     * 0 if the code is from the current step, 1 if from the step before, null if it's wrong or too old.
     */
    public function age(LectureSession $session, string $code): ?int
    {
        $nowStep = $this->currentStep($session);

        if ($code === $this->codeFor($session, $nowStep)) {
            return 0;
        }

        if ($code === $this->codeFor($session, $nowStep - 1)) {
            return 1;
        }

        return null;
    }
}

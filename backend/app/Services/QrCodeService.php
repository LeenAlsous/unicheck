<?php

namespace App\Services;

use App\Models\LectureSession;
use Carbon\CarbonInterface;

/**
 * Rotating QR codes for a lecture session.
 *
 * The instructor app makes the codes itself from the session's `qr_secret`, the same way an
 * authenticator app makes its 6-digit codes, so the server only has to check them:
 *
 *     step      = floor(unix_seconds / qr_step_seconds)
 *     signature = base64url(HMAC-SHA256(key: qr_secret, message: "{session_id}.{step}")),
 *                 without "=" padding, first 22 characters
 *     code      = "UC1.{session_id}.{step}.{signature}"
 *
 * A code is accepted during its own step and the next one, so a screenshot sent to a friend
 * stops working after 10–20 seconds.
 */
final class QrCodeService
{
    /** Marks a QR code as a UniCheck attendance code (version 1 of the format). */
    public const PREFIX = 'UC1';

    private const SIGNATURE_LENGTH = 22;

    /**
     * The step number at the given time (now by default).
     */
    public function currentStep(LectureSession $session, ?CarbonInterface $at = null): int
    {
        return intdiv(($at ?? now())->getTimestamp(), $session->qr_step_seconds);
    }

    /**
     * The code for one step. The server uses it in tests and to check scanned codes.
     */
    public function codeFor(LectureSession $session, int $step): string
    {
        return implode('.', [self::PREFIX, $session->id, $step, $this->signature($session, $step)]);
    }

    /**
     * The session ID inside a scanned code, or null if it isn't a UniCheck code.
     */
    public function sessionIdFrom(string $code): ?int
    {
        $parts = explode('.', $code);

        if (count($parts) !== 4 || $parts[0] !== self::PREFIX || ! ctype_digit($parts[1])) {
            return null;
        }

        return (int) $parts[1];
    }

    /**
     * How old a scanned code is: 0 for the current step, 1 for the previous one.
     *
     * Null when the code is older than that, from the future, for another session or forged.
     */
    public function age(LectureSession $session, string $code): ?int
    {
        $parts = explode('.', $code);

        if (count($parts) !== 4 || ! ctype_digit($parts[2])) {
            return null;
        }

        $step = (int) $parts[2];
        $age = $this->currentStep($session) - $step;

        if ($age < 0 || $age > 1) {
            return null;
        }

        return hash_equals($this->codeFor($session, $step), $code) ? $age : null;
    }

    private function signature(LectureSession $session, int $step): string
    {
        $mac = hash_hmac('sha256', "{$session->id}.{$step}", $session->qr_secret, true);
        $base64Url = rtrim(strtr(base64_encode($mac), '+/', '-_'), '=');

        return substr($base64Url, 0, self::SIGNATURE_LENGTH);
    }
}

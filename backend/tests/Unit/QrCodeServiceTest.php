<?php

namespace Tests\Unit;

use App\Models\LectureSession;
use App\Services\QrCodeService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    private QrCodeService $qrCodes;

    private LectureSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-09 10:00:00', 'UTC'));
        $this->qrCodes = new QrCodeService;
        $this->session = (new LectureSession)->forceFill([
            'id' => 42,
            'qr_secret' => 'test-secret',
            'qr_step_seconds' => 10,
        ]);
    }

    /**
     * The instructor app must produce exactly this code for the same inputs.
     */
    public function test_matches_the_reference_code(): void
    {
        $this->assertSame(179421840, $this->qrCodes->currentStep($this->session));
        $this->assertSame('42-179421840-7c8a89cf', $this->qrCodes->codeFor($this->session, 179421840));
    }

    public function test_current_and_previous_codes_are_accepted(): void
    {
        $code = $this->qrCodes->codeFor($this->session, 179421840);

        $this->assertSame(0, $this->qrCodes->age($this->session, $code));

        $this->travel(10)->seconds();
        $this->assertSame(1, $this->qrCodes->age($this->session, $code));

        $this->travel(10)->seconds();
        $this->assertNull($this->qrCodes->age($this->session, $code));
    }

    public function test_codes_from_the_future_or_with_a_wrong_signature_are_rejected(): void
    {
        $this->assertNull($this->qrCodes->age($this->session, $this->qrCodes->codeFor($this->session, 179421841)));
        $this->assertNull($this->qrCodes->age($this->session, '42-179421840-aaaaaaaa'));
        $this->assertNull($this->qrCodes->age($this->session, 'not a code'));
    }

    public function test_reads_the_session_id(): void
    {
        $this->assertSame(42, $this->qrCodes->sessionIdFrom('42-179421840-7c8a89cf'));
        $this->assertNull($this->qrCodes->sessionIdFrom('https://example.com'));
        $this->assertNull($this->qrCodes->sessionIdFrom('abc-1-x'));
    }
}

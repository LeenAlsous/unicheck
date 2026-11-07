namespace UniCheck.Api.Domain;

/// <summary>One lecture of a section, with its own check-in window and QR secret.</summary>
public class LectureSession
{
    public Guid Id { get; set; }

    public Guid SectionId { get; set; }

    public Section Section { get; set; } = null!;

    public Guid RoomId { get; set; }

    public Room Room { get; set; } = null!;

    public SessionStatus Status { get; set; } = SessionStatus.Scheduled;

    public DateTimeOffset CreatedAt { get; set; } = DateTimeOffset.UtcNow;

    public DateTimeOffset? OpenedAt { get; set; }

    /// <summary>When check-in closes automatically; the instructor can close it earlier.</summary>
    public DateTimeOffset? ClosesAt { get; set; }

    public DateTimeOffset? ClosedAt { get; set; }

    /// <summary>Key used to sign the rotating QR tokens. Never sent to clients.</summary>
    public byte[] QrSecret { get; set; } = [];

    /// <summary>How often the QR changes.</summary>
    public int QrStepSeconds { get; set; } = 10;

    public List<AttendanceRecord> Records { get; set; } = [];
}

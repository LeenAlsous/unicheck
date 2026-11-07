namespace UniCheck.Api.Domain;

/// <summary>A student's attendance for one lecture session.</summary>
public class AttendanceRecord
{
    public Guid Id { get; set; }

    public Guid LectureSessionId { get; set; }

    public LectureSession LectureSession { get; set; } = null!;

    public Guid StudentId { get; set; }

    public User Student { get; set; } = null!;

    public AttendanceStatus Status { get; set; }

    public AttendanceSource Source { get; set; }

    /// <summary>Server time of the check-in or manual change.</summary>
    public DateTimeOffset RecordedAt { get; set; } = DateTimeOffset.UtcNow;

    public double? Latitude { get; set; }

    public double? Longitude { get; set; }

    /// <summary>GPS accuracy reported by the phone, in meters.</summary>
    public double? AccuracyMeters { get; set; }

    public Guid? DeviceId { get; set; }

    public Device? Device { get; set; }

    public AttendanceFlags Flags { get; set; } = AttendanceFlags.None;

    /// <summary>Instructor note for manual changes, e.g. an excuse reason.</summary>
    public string? Note { get; set; }
}

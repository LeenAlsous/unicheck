namespace UniCheck.Api.Domain;

/// <summary>What a user is allowed to do in the system.</summary>
public enum UserRole
{
    Student,
    Instructor,
    Admin,
}

/// <summary>Lifecycle of a lecture session's check-in window.</summary>
public enum SessionStatus
{
    /// <summary>Created but check-in has not been opened yet.</summary>
    Scheduled,

    /// <summary>The rotating QR is live and students can check in.</summary>
    Open,

    /// <summary>Check-in is over; records can only be changed manually.</summary>
    Closed,
}

/// <summary>A student's attendance result for one session.</summary>
public enum AttendanceStatus
{
    Present,
    Absent,
    Excused,
}

/// <summary>How an attendance record was created.</summary>
public enum AttendanceSource
{
    /// <summary>The student scanned the QR in the app.</summary>
    Scan,

    /// <summary>An instructor set or corrected it in the portal.</summary>
    Manual,
}

/// <summary>Signals that make a check-in worth an instructor's review.</summary>
[Flags]
public enum AttendanceFlags
{
    None = 0,
    WeakLocationSignal = 1 << 0,
    FarFromClass = 1 << 1,
    TokenNearExpiry = 1 << 2,
    RecentDeviceChange = 1 << 3,
}

/// <summary>Approval state of a student's registered phone.</summary>
public enum DeviceStatus
{
    /// <summary>Waiting for an admin to approve a device change.</summary>
    Pending,

    /// <summary>The device the student checks in with.</summary>
    Active,

    /// <summary>Replaced or blocked; check-ins from it are rejected.</summary>
    Revoked,
}

namespace UniCheck.Api.Domain;

/// <summary>A student, instructor or admin account.</summary>
public class User
{
    public Guid Id { get; set; }

    /// <summary>Student or staff number; this is what users log in with.</summary>
    public required string UniversityId { get; set; }

    public required string FullName { get; set; }

    public string? Email { get; set; }

    public string PasswordHash { get; set; } = "";

    public UserRole Role { get; set; }

    public bool IsActive { get; set; } = true;

    public DateTimeOffset CreatedAt { get; set; } = DateTimeOffset.UtcNow;

    public List<Enrollment> Enrollments { get; set; } = [];

    public List<Device> Devices { get; set; } = [];
}

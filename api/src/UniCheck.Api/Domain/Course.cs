namespace UniCheck.Api.Domain;

/// <summary>A course in the catalog, e.g. CS330 Databases.</summary>
public class Course
{
    public Guid Id { get; set; }

    public required string Code { get; set; }

    public required string Name { get; set; }

    /// <summary>Absences allowed before the student and instructor are alerted.</summary>
    public int MaxAbsences { get; set; }

    public List<Section> Sections { get; set; } = [];
}

/// <summary>One class group of a course in a semester, taught by one instructor.</summary>
public class Section
{
    public Guid Id { get; set; }

    public Guid CourseId { get; set; }

    public Course Course { get; set; } = null!;

    /// <summary>Section number shown to users, e.g. "1".</summary>
    public required string Number { get; set; }

    /// <summary>Semester label, e.g. "2026-1".</summary>
    public required string Semester { get; set; }

    public Guid InstructorId { get; set; }

    public User Instructor { get; set; } = null!;

    /// <summary>Room used for new sessions unless the instructor picks another.</summary>
    public Guid? DefaultRoomId { get; set; }

    public Room? DefaultRoom { get; set; }

    public List<Enrollment> Enrollments { get; set; } = [];

    public List<LectureSession> Sessions { get; set; } = [];
}

/// <summary>A student registered in a section.</summary>
public class Enrollment
{
    public Guid SectionId { get; set; }

    public Section Section { get; set; } = null!;

    public Guid StudentId { get; set; }

    public User Student { get; set; } = null!;

    public DateTimeOffset EnrolledAt { get; set; } = DateTimeOffset.UtcNow;
}

using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;
using UniCheck.Api.Domain;

namespace UniCheck.Api.Data;

/// <summary>
/// Fills an empty database with fake users, rooms and courses for development.
/// Runs only when <c>Seed:DevData</c> is true (set in appsettings.Development.json).
/// </summary>
public static class DevDataSeeder
{
    /// <summary>Password for every seeded account. Development only.</summary>
    public const string DevPassword = "UniCheck@2026";

    public static async Task SeedAsync(AppDbContext db, IPasswordHasher<User> hasher)
    {
        if (await db.Users.AnyAsync())
        {
            return;
        }

        var admin = NewUser("A0001", "System Admin", UserRole.Admin, hasher);
        var instructor1 = NewUser("I1001", "Dr. Instructor One", UserRole.Instructor, hasher);
        var instructor2 = NewUser("I1002", "Dr. Instructor Two", UserRole.Instructor, hasher);
        var students = Enumerable.Range(1, 12)
            .Select(i => NewUser($"S{i:0000}", $"Student {i}", UserRole.Student, hasher))
            .ToList();

        // Placeholder coordinates: replace with the real rooms (pin them on the map in the portal).
        var roomA = new Room { Building = "IT Building", Name = "101", Latitude = 31.9539, Longitude = 35.9106 };
        var roomB = new Room { Building = "IT Building", Name = "204", Latitude = 31.9541, Longitude = 35.9110 };

        var databases = new Course { Code = "CS330", Name = "Database Systems", MaxAbsences = 6 };
        var dataStructures = new Course { Code = "CS201", Name = "Data Structures", MaxAbsences = 6 };

        var sectionA = new Section
        {
            Course = databases, Number = "1", Semester = "2026-1",
            Instructor = instructor1, DefaultRoom = roomA,
        };
        var sectionB = new Section
        {
            Course = dataStructures, Number = "1", Semester = "2026-1",
            Instructor = instructor2, DefaultRoom = roomB,
        };

        // Students 1–8 in databases, 5–12 in data structures (5–8 take both).
        sectionA.Enrollments.AddRange(students[..8].Select(s => new Enrollment { Student = s }));
        sectionB.Enrollments.AddRange(students[4..].Select(s => new Enrollment { Student = s }));

        db.Users.AddRange([admin, instructor1, instructor2, .. students]);
        db.Sections.AddRange(sectionA, sectionB);
        await db.SaveChangesAsync();
    }

    private static User NewUser(string universityId, string name, UserRole role, IPasswordHasher<User> hasher)
    {
        var user = new User { UniversityId = universityId, FullName = name, Role = role };
        user.PasswordHash = hasher.HashPassword(user, DevPassword);
        return user;
    }
}

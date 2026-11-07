using Microsoft.EntityFrameworkCore;
using UniCheck.Api.Domain;

namespace UniCheck.Api.Data;

/// <summary>EF Core database context for UniCheck.</summary>
public class AppDbContext(DbContextOptions<AppDbContext> options) : DbContext(options)
{
    public DbSet<User> Users => Set<User>();
    public DbSet<Course> Courses => Set<Course>();
    public DbSet<Section> Sections => Set<Section>();
    public DbSet<Enrollment> Enrollments => Set<Enrollment>();
    public DbSet<Room> Rooms => Set<Room>();
    public DbSet<LectureSession> LectureSessions => Set<LectureSession>();
    public DbSet<AttendanceRecord> AttendanceRecords => Set<AttendanceRecord>();
    public DbSet<Device> Devices => Set<Device>();

    protected override void ConfigureConventions(ModelConfigurationBuilder builder)
    {
        // Store enums as readable text instead of numbers.
        builder.Properties<UserRole>().HaveConversion<string>().HaveMaxLength(20);
        builder.Properties<SessionStatus>().HaveConversion<string>().HaveMaxLength(20);
        builder.Properties<AttendanceStatus>().HaveConversion<string>().HaveMaxLength(20);
        builder.Properties<AttendanceSource>().HaveConversion<string>().HaveMaxLength(20);
        builder.Properties<DeviceStatus>().HaveConversion<string>().HaveMaxLength(20);
    }

    protected override void OnModelCreating(ModelBuilder model)
    {
        model.Entity<User>(e =>
        {
            e.HasIndex(u => u.UniversityId).IsUnique();
            e.HasIndex(u => u.Email).IsUnique();
            e.Property(u => u.UniversityId).HasMaxLength(30);
            e.Property(u => u.FullName).HasMaxLength(150);
            e.Property(u => u.Email).HasMaxLength(200);
        });

        model.Entity<Course>(e =>
        {
            e.HasIndex(c => c.Code).IsUnique();
            e.Property(c => c.Code).HasMaxLength(20);
            e.Property(c => c.Name).HasMaxLength(150);
        });

        model.Entity<Section>(e =>
        {
            e.HasIndex(s => new { s.CourseId, s.Semester, s.Number }).IsUnique();
            e.Property(s => s.Number).HasMaxLength(10);
            e.Property(s => s.Semester).HasMaxLength(20);
            e.HasOne(s => s.Instructor).WithMany().OnDelete(DeleteBehavior.Restrict);
            e.HasOne(s => s.DefaultRoom).WithMany().OnDelete(DeleteBehavior.SetNull);
        });

        model.Entity<Enrollment>(e =>
        {
            e.HasKey(en => new { en.SectionId, en.StudentId });
            e.HasOne(en => en.Student).WithMany(u => u.Enrollments).OnDelete(DeleteBehavior.Cascade);
        });

        model.Entity<Room>(e =>
        {
            e.HasIndex(r => new { r.Building, r.Name }).IsUnique();
            e.Property(r => r.Building).HasMaxLength(100);
            e.Property(r => r.Name).HasMaxLength(50);
        });

        model.Entity<LectureSession>(e =>
        {
            e.HasIndex(s => new { s.SectionId, s.Status });
            e.HasOne(s => s.Room).WithMany().OnDelete(DeleteBehavior.Restrict);
        });

        model.Entity<AttendanceRecord>(e =>
        {
            // One record per student per session.
            e.HasIndex(a => new { a.LectureSessionId, a.StudentId }).IsUnique();
            e.HasOne(a => a.Student).WithMany().OnDelete(DeleteBehavior.Restrict);
            e.HasOne(a => a.Device).WithMany().OnDelete(DeleteBehavior.SetNull);
            e.Property(a => a.Note).HasMaxLength(500);
        });

        model.Entity<Device>(e =>
        {
            e.HasIndex(d => new { d.StudentId, d.Status });
            e.HasIndex(d => d.PublicKey).IsUnique();
            e.Property(d => d.Platform).HasMaxLength(20);
            e.Property(d => d.Model).HasMaxLength(100);
        });
    }
}

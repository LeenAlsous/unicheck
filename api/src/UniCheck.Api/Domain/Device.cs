namespace UniCheck.Api.Domain;

/// <summary>
/// A student's phone. The app creates a key pair in secure hardware and registers the
/// public key here; check-ins are signed with the private key so they can't come from
/// another phone.
/// </summary>
public class Device
{
    public Guid Id { get; set; }

    public Guid StudentId { get; set; }

    public User Student { get; set; } = null!;

    public required string PublicKey { get; set; }

    /// <summary>"android" or "ios".</summary>
    public required string Platform { get; set; }

    public string? Model { get; set; }

    public DeviceStatus Status { get; set; } = DeviceStatus.Pending;

    public DateTimeOffset RegisteredAt { get; set; } = DateTimeOffset.UtcNow;

    public DateTimeOffset? ApprovedAt { get; set; }
}

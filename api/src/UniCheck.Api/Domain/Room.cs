namespace UniCheck.Api.Domain;

/// <summary>A lecture room and the area students must be in to check in.</summary>
public class Room
{
    public Guid Id { get; set; }

    public required string Building { get; set; }

    public required string Name { get; set; }

    public double Latitude { get; set; }

    public double Longitude { get; set; }

    /// <summary>
    /// Allowed distance from the room point. Building-sized (50–100 m), because
    /// indoor GPS is not accurate enough for a room-sized radius.
    /// </summary>
    public int RadiusMeters { get; set; } = 75;
}

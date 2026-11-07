namespace UniCheck.Api.Auth;

/// <summary>Settings for issuing and validating access tokens (section "Jwt" in appsettings).</summary>
public class JwtOptions
{
    public const string Section = "Jwt";

    public string Issuer { get; set; } = "unicheck";

    public string Audience { get; set; } = "unicheck";

    /// <summary>At least 32 characters. Set from environment variables in production, never in the repo.</summary>
    public string SigningKey { get; set; } = "";

    public int ExpiryMinutes { get; set; } = 720;
}

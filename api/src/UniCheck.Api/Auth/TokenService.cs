using System.Security.Claims;
using System.Text;
using Microsoft.Extensions.Options;
using Microsoft.IdentityModel.JsonWebTokens;
using Microsoft.IdentityModel.Tokens;
using UniCheck.Api.Domain;

namespace UniCheck.Api.Auth;

/// <summary>Creates signed JWT access tokens for logged-in users.</summary>
public class TokenService(IOptions<JwtOptions> options)
{
    /// <summary>Claim names used in our tokens (kept short; inbound claim mapping is off).</summary>
    public static class Claims
    {
        public const string UserId = "sub";
        public const string Name = "name";
        public const string Role = "role";
        public const string UniversityId = "university_id";
    }

    private readonly JwtOptions _options = options.Value;

    public static SymmetricSecurityKey KeyFrom(JwtOptions options) =>
        new(Encoding.UTF8.GetBytes(options.SigningKey));

    public (string Token, DateTimeOffset ExpiresAt) CreateAccessToken(User user)
    {
        var expiresAt = DateTimeOffset.UtcNow.AddMinutes(_options.ExpiryMinutes);
        var descriptor = new SecurityTokenDescriptor
        {
            Issuer = _options.Issuer,
            Audience = _options.Audience,
            Expires = expiresAt.UtcDateTime,
            Subject = new ClaimsIdentity(
            [
                new Claim(Claims.UserId, user.Id.ToString()),
                new Claim(Claims.Name, user.FullName),
                new Claim(Claims.Role, user.Role.ToString()),
                new Claim(Claims.UniversityId, user.UniversityId),
            ]),
            SigningCredentials = new SigningCredentials(KeyFrom(_options), SecurityAlgorithms.HmacSha256),
        };

        return (new JsonWebTokenHandler().CreateToken(descriptor), expiresAt);
    }
}

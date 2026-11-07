using System.Security.Claims;

namespace UniCheck.Api.Auth;

/// <summary>Helpers for reading the logged-in user from a request.</summary>
public static class ClaimsPrincipalExtensions
{
    /// <summary>The user's id from the access token.</summary>
    public static Guid GetUserId(this ClaimsPrincipal principal) =>
        Guid.Parse(principal.FindFirstValue(TokenService.Claims.UserId)
            ?? throw new InvalidOperationException("Access token has no user id."));
}

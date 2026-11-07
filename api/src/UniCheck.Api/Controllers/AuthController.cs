using System.ComponentModel.DataAnnotations;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using UniCheck.Api.Auth;
using UniCheck.Api.Data;
using UniCheck.Api.Domain;

namespace UniCheck.Api.Controllers;

/// <summary>Login and the current user's profile.</summary>
[ApiController]
[Route("api/auth")]
public class AuthController(AppDbContext db, IPasswordHasher<User> hasher, TokenService tokens) : ControllerBase
{
    /// <summary>Logs in with a university id and password and returns an access token.</summary>
    [HttpPost("login")]
    [AllowAnonymous]
    public async Task<ActionResult<LoginResponse>> Login(LoginRequest request)
    {
        var user = await db.Users.SingleOrDefaultAsync(u => u.UniversityId == request.UniversityId.Trim());
        if (user is null || !user.IsActive)
        {
            return InvalidLogin();
        }

        var result = hasher.VerifyHashedPassword(user, user.PasswordHash, request.Password);
        if (result == PasswordVerificationResult.Failed)
        {
            return InvalidLogin();
        }

        if (result == PasswordVerificationResult.SuccessRehashNeeded)
        {
            user.PasswordHash = hasher.HashPassword(user, request.Password);
            await db.SaveChangesAsync();
        }

        var (token, expiresAt) = tokens.CreateAccessToken(user);
        return new LoginResponse(token, expiresAt, UserDto.From(user));
    }

    /// <summary>Returns the logged-in user.</summary>
    [HttpGet("me")]
    [Authorize]
    public async Task<ActionResult<UserDto>> Me()
    {
        var user = await db.Users.FindAsync(User.GetUserId());
        return user is null ? Unauthorized() : UserDto.From(user);
    }

    // Same message for unknown user and wrong password, so ids can't be guessed.
    private UnauthorizedObjectResult InvalidLogin() =>
        Unauthorized(new ProblemDetails { Title = "Invalid university ID or password." });
}

/// <summary>Login form.</summary>
public record LoginRequest([Required] string UniversityId, [Required] string Password);

/// <summary>Access token plus the user it belongs to.</summary>
public record LoginResponse(string AccessToken, DateTimeOffset ExpiresAt, UserDto User);

/// <summary>Public view of a user account.</summary>
public record UserDto(Guid Id, string UniversityId, string FullName, string? Email, UserRole Role)
{
    public static UserDto From(User u) => new(u.Id, u.UniversityId, u.FullName, u.Email, u.Role);
}

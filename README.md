# UniCheck

Barcode-based smart attendance tracking for universities. Instructors open a lecture
session and project a QR code that changes every few seconds; students scan it with the
mobile app to register attendance. Location checks, device binding and a short check-in
window make it hard to mark attendance without being in the room.

## Repository layout

| Folder | What it is | Stack |
|---|---|---|
| [`api/`](api) | Backend REST API + real-time updates | ASP.NET Core (.NET 10), EF Core, SignalR |
| [`portal/`](portal) | Instructor / admin web portal | React + TypeScript (Vite) |
| [`app/`](app) | Student mobile app | Flutter |
| [`docs/`](docs) | Plan, design notes, report material | Markdown |

Each folder has its own README with setup and run instructions.

## Getting started

Requirements:

- [.NET SDK 10](https://dotnet.microsoft.com/download)
- [Node.js](https://nodejs.org/) 20 or newer
- [Flutter](https://docs.flutter.dev/get-started/install) (stable channel)
- A database server (PostgreSQL or SQL Server — see [docs/plan.md](docs/plan.md))

```bash
git clone https://github.com/LeenAlsous/unicheck.git
cd unicheck
```

Then follow the README in the folder you are working on.

## Working together

- Work on a branch (`feature/<short-name>`) and open a pull request into `main`.
- Keep changes to one folder per pull request when you can; if an API change needs a
  matching portal or app change, put them in the same pull request.
- Never commit secrets (connection strings, keys). Use `dotnet user-secrets` for the API
  and `.env.local` for the portal.

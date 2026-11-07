# UniCheck API

ASP.NET Core Web API (.NET 10) for UniCheck: authentication, courses, lecture sessions,
rotating QR tokens, check-in validation, reports and absence alerts.

## Layout

```
api/
├── UniCheck.slnx
├── dotnet-tools.json            ← pins the dotnet-ef tool version
└── src/UniCheck.Api/
    ├── Domain/                  ← entities and enums (User, Course, Section, Room, LectureSession, ...)
    ├── Data/                    ← AppDbContext, dev seed data, EF Core migrations
    ├── Auth/                    ← JWT settings and token creation
    ├── Controllers/             ← HTTP endpoints
    └── UniCheck.Api.http        ← ready-made requests (VS Code REST Client / Rider / Visual Studio)
```

## First-time setup (macOS)

1. Install the .NET SDK and PostgreSQL:

   ```bash
   brew install --cask dotnet-sdk
   brew install postgresql@17 && brew services start postgresql@17
   ```

2. Create the local database user the API connects with (development only):

   ```bash
   /opt/homebrew/opt/postgresql@17/bin/psql -d postgres -c "CREATE ROLE unicheck LOGIN PASSWORD 'unicheck' CREATEDB;"
   ```

   On Windows, install PostgreSQL with the official installer and run the same
   `CREATE ROLE` command in pgAdmin or `psql`.

3. Restore the EF Core tool:

   ```bash
   cd api
   dotnet tool restore
   ```

## Run

```bash
cd api/src/UniCheck.Api
dotnet run --launch-profile http
```

- API: http://localhost:5161
- API docs (Scalar): http://localhost:5161/scalar
- Health check: http://localhost:5161/health

On startup the API applies any pending migrations. In development it also fills an empty
database with fake data (`Seed:DevData` in `appsettings.Development.json`).

## Seeded development accounts

All seeded accounts use the password in `DevDataSeeder.DevPassword`
(`src/UniCheck.Api/Data/DevDataSeeder.cs`).

| University ID | Role |
|---|---|
| `A0001` | Admin |
| `I1001`, `I1002` | Instructor |
| `S0001` … `S0012` | Student |

Courses: CS330 (students 1–8, instructor I1001) and CS201 (students 5–12, instructor I1002).

To start over with fresh data, drop the database and run the API again:

```bash
/opt/homebrew/opt/postgresql@17/bin/dropdb -U unicheck unicheck
```

## Changing the database model

After editing an entity in `Domain/` or `AppDbContext`:

```bash
cd api
dotnet ef migrations add <DescriptiveName> -p src/UniCheck.Api -o Data/Migrations
```

Commit the generated migration files. They are applied automatically the next time the API starts.

## Endpoints so far

| Method | Path | Auth | Purpose |
|---|---|---|---|
| `POST` | `/api/auth/login` | – | Log in with `universityId` + `password`, returns a JWT |
| `GET` | `/api/auth/me` | Any role | Current user |
| `GET` | `/health` | – | Liveness check for hosting |

## Configuration

| Key | Development | Production |
|---|---|---|
| `ConnectionStrings:Default` | local `unicheck` database | set as an app setting on the host |
| `Jwt:SigningKey` | dev-only key in `appsettings.Development.json` | long random value, set on the host, never committed |
| `Cors:Origins` | `http://localhost:5173` (Vite dev server) | the portal's URL |
| `Seed:DevData` | `true` | `false` |

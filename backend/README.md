# UniCheck Backend

Laravel 13 REST API for UniCheck: login, courses, lecture sessions, rotating QR
validation, check-ins, reports and absence alerts. Both Flutter clients (students and
instructors) talk to this API.

## Layout

```
backend/
├── app/
│   ├── Enums/                    ← UserRole, SessionStatus, AttendanceStatus, DeviceStatus, ...
│   ├── Models/                   ← User, Course, Section, Room, LectureSession, AttendanceRecord, Device
│   └── Http/
│       ├── Controllers/Api/      ← API endpoints
│       ├── Requests/             ← input validation
│       ├── Resources/            ← JSON shapes returned to the apps
│       └── Middleware/           ← EnsureUserHasRole (`role:instructor,admin`)
├── database/
│   ├── migrations/               ← table definitions
│   └── seeders/DatabaseSeeder.php← fake development data
├── routes/api.php                ← all API routes (prefixed with /api)
└── tests/Feature/                ← automated tests
```

## First-time setup

### macOS

```bash
brew install php composer mysql@8.4
brew services start mysql@8.4
/opt/homebrew/opt/mysql@8.4/bin/mysql -uroot -e "CREATE DATABASE unicheck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

### Windows

Install [XAMPP](https://www.apachefriends.org/) (for MySQL) and
[Composer](https://getcomposer.org/download/). Start **MySQL** in the XAMPP control
panel, then create a database called `unicheck` in phpMyAdmin (collation
`utf8mb4_unicode_ci`). Make sure `php -v` works in a terminal (XAMPP's `php` folder must
be on your PATH) and shows PHP 8.3 or newer.

### Both

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

`.env.example` already matches a default local MySQL / XAMPP install (user `root`, no
password, database `unicheck`). Change the `DB_*` values in your own `.env` if yours differs.
Never commit `.env`.

## Run

```bash
php artisan serve
```

- API: http://localhost:8000/api
- API docs (try every endpoint in the browser): http://localhost:8000/docs/api
- Health check: http://localhost:8000/up

To call endpoints that need login from the API docs: send `POST /api/auth/login`, copy the
`token` from the response, and paste it into the **Auth** / bearer token field.

To reach the API from a phone on the same Wi-Fi:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

then use `http://<your-computer's-IP>:8000/api` in the app.

## Seeded development accounts

All seeded accounts use the password in `DatabaseSeeder::DEV_PASSWORD`
(`database/seeders/DatabaseSeeder.php`).

| University ID | Role |
|---|---|
| `A0001` | Admin |
| `I1001`, `I1002` | Instructor |
| `S0001` … `S0012` | Student |

Courses: CS330 (students 1–8, instructor I1001) and CS201 (students 5–12, instructor I1002).
Room coordinates are placeholders until the real rooms are entered.

Start over with a clean database and fresh fake data:

```bash
php artisan migrate:fresh --seed
```

## Authentication

Laravel Sanctum bearer tokens. The app sends `POST /api/auth/login` with
`university_id` + `password`, stores the returned `token`, and sends it on every request:

```
Authorization: Bearer <token>
Accept: application/json
```

Tokens expire after 30 days (`SANCTUM_TOKEN_EXPIRATION`, in minutes).

Restrict a route to some roles with the `role` middleware:

```php
Route::middleware(['auth:sanctum', 'role:instructor,admin'])->group(function () { ... });
```

## Endpoints so far

| Method | Path | Auth | Purpose |
|---|---|---|---|
| `POST` | `/api/auth/login` | – | Log in, returns a bearer token and the user |
| `GET` | `/api/auth/me` | Any role | Current user |
| `POST` | `/api/auth/logout` | Any role | Revoke the current token |
| `GET` | `/up` | – | Health check for hosting |

## Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database, so they don't touch your MySQL data.

## Code style

```bash
vendor/bin/pint
```

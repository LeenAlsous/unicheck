# UniCheck Backend

Laravel 13 REST API for UniCheck: login, courses, lecture sessions, rotating QR
validation, check-ins, reports and absence alerts. Both Flutter clients (students and
instructors) talk to this API.

## Layout

```
backend/
├── app/
│   ├── Models/                   ← User, Course, Section, Room, LectureSession, AttendanceRecord, Device
│   ├── Services/QrCodeService.php← makes and checks the rotating QR codes
│   └── Http/
│       ├── Controllers/Api/      ← API endpoints: validation, permission checks and logic are all here
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

All paths start with `/api`. The API docs page shows the full request and response of each one.

| Method | Path | Who | Purpose |
|---|---|---|---|
| `POST` | `/auth/login` | – | Log in, returns a bearer token and the user |
| `GET` | `/auth/me` | Anyone | Current user |
| `POST` | `/auth/logout` | Anyone | Revoke the current token |
| `GET` | `/sections` | Anyone | Home screen: sections I teach / I'm enrolled in (admins: all), with `open_session` while check-in runs |
| `GET` | `/sections/{section}` | Anyone in the section | One section |
| `POST` | `/check-ins` | Student | Check in with the scanned QR text and the phone's location |
| `GET` | `/rooms` | Instructor | Rooms to choose from |
| `GET` | `/sections/{section}/students` | Instructor | Class list |
| `GET` | `/sections/{section}/sessions` | Instructor | Past and current lectures with present/absent counts |
| `POST` | `/sections/{section}/sessions` | Instructor | Start a lecture: opens check-in (15 min by default) and returns `qr_secret` |
| `GET` | `/sessions/{session}` | Instructor | A lecture with its `qr_secret`, to show the QR again (e.g. on the lecture-room PC) |
| `POST` | `/sessions/{session}/close` | Instructor | End the lecture; everyone who didn't check in becomes absent |
| `GET` | `/sessions/{session}/attendance` | Instructor | Live attendance list (poll every ~3 s) |
| `PUT` | `/sessions/{session}/attendance/{student}` | Instructor | Mark one student present / absent / excused, with a note |
| `GET` | `/up` | – | Health check for hosting |

"Instructor" means the section's own instructor; admins can do everything instructors can.

## Rotating QR code

The instructor app makes the QR codes itself (like an authenticator app), so the projector
screen keeps working without calling the server every 10 seconds. With `qr_secret`,
`qr_step_seconds` and `server_time_ms` from the session response:

```
step    = floor(server_now_seconds / qr_step_seconds)
hash    = first 8 characters of sha256(qr_secret + step), in lowercase hex
          (qr_secret and step joined as text, e.g. "test-secret179421840")
QR text = "{session_id}-{step}-{hash}"
```

`server_now` is the phone's clock corrected by the difference to `server_time_ms`, so a
phone with the wrong time still shows valid codes. Draw a new QR whenever `step` changes.

Check your Flutter code against this example: session `42`, secret `test-secret`, 10-second
steps, at 2026-11-09 10:00:00 UTC → `42-179421840-7c8a89cf`
(the same example is in `tests/Unit/QrCodeServiceTest.php`).

## Check-in rules

`POST /check-ins` checks these in order (`app/Http/Controllers/Api/CheckInController.php`). If one fails it
returns **422** with a `message` to show the student as-is:

1. The QR text is a UniCheck code for a lecture that exists.
2. The student is enrolled in that section.
3. Check-in is open (the lecture started and its window hasn't ended).
4. If they already checked in, the same record comes back (**200**), so scanning twice is fine.
   If the instructor already set their attendance by hand, the scan is refused.
5. The code is from the current 10-second step or the one before. A screenshot sent to a
   friend stops working within 20 seconds.
6. The location isn't faked (`is_mocked`, from Android's `Location.isMocked`).
7. The phone is within the room's radius (75 m by default, building-sized because indoor
   GPS is inaccurate). Up to 50 m of the reported GPS error is forgiven.

A successful check-in returns **201**. Some check-ins are accepted but get `flags` for the
instructor to review: `token_near_expiry` (used the previous code), `weak_location_signal`
(GPS accuracy worse than 50 m), and `far_from_class` (more than 50 m from the middle of the
students who already checked in, once there are at least 5).

Not done yet: device binding (one phone per student, signed check-ins).

## Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database, so they don't touch your MySQL data.

## Code style

```bash
vendor/bin/pint
```

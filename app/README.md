# UniCheck App

One Flutter app for both roles; the screens shown depend on who logs in.

- **Students** (Android / iOS): login, device registration, QR scanning to check in,
  attendance history and absence notifications.
- **Instructors** (Android / iOS, and a web build for the lecture-room PC): courses and
  student lists, starting a session with a full-screen rotating QR, live attendance,
  manual corrections and reports.

**Done so far:** login with university ID, staying logged in after restarting, log out, and the
home screen for each role (student: "My courses" with a *Check-in open* label; instructor:
"My sections" with student count and room).

## Run it

1. Start the backend first (see [`../backend/README.md`](../backend/README.md)):

   ```bash
   cd ../backend && php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Run the app:

   ```bash
   flutter pub get
   flutter run                 # iOS simulator, Android emulator, or pick a device
   flutter run -d chrome       # web (instructor screens on the lecture-room PC)
   ```

The app finds the backend by itself on the iOS simulator and web (`localhost`) and on the
Android emulator (`10.0.2.2`). On a **real phone**, both must be on the same Wi-Fi; pass your
computer's IP address:

```bash
flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000/api
```

(Find the IP with `ipconfig getifaddr en0` on a Mac or `ipconfig` on Windows.)

Log in with a seeded account from the backend README, e.g. `S0001` (student) or `I1001`
(instructor).

## Layout

```
lib/
├── main.dart                 ← creates the API client and repositories, starts the app
├── app.dart                  ← theme; AuthGate picks login / student home / instructor home
├── core/
│   ├── config.dart           ← backend URL
│   ├── api/                  ← ApiClient (Dio + login token), ApiException (readable errors)
│   └── storage/              ← TokenStorage (secure storage)
└── features/
    ├── auth/                 ← login screen, AuthCubit (who is logged in), LoginCubit
    ├── sections/             ← Section model, repository, cubit, list widgets
    └── home/                 ← StudentHomeScreen, InstructorHomeScreen
```

Each feature has `models/` (JSON → Dart), `data/` (API calls), `cubit/` (state) and
`screens/` or `widgets/` (UI). State lives in cubits (`flutter_bloc`); screens only read it.

How errors work: when the backend refuses something it sends a message meant for the user
(for example "This QR code has expired."). `ApiClient` turns it into an `ApiException`
whose `message` the screen shows as-is, so error texts are written once, in the backend.
If the login token stops working, the app goes back to the login screen by itself.

## Tests and checks

```bash
flutter test
flutter analyze
dart format lib test        # line length 120, set in analysis_options.yaml
```

Tests mock the repositories with `mocktail`; the JSON in `test/helpers/fixtures.dart` is
copied from real backend responses.

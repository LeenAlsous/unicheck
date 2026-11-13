# UniCheck App

One Flutter app for both roles; the screens shown depend on who logs in.

- **Students** (Android / iOS): login, device registration, QR scanning to check in,
  attendance history and absence notifications.
- **Instructors** (Android / iOS, and a web build for the lecture-room PC): courses and
  student lists, starting a session with a full-screen rotating QR, live attendance,
  manual corrections and reports.

**Done so far:** login with university ID, staying logged in after restarting, log out, and the
home screen for each role (student: "My courses" with a *Check-in open* label; instructor:
"My sections" with student count, room and a *Lecture running* label).

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

## Code layout

Kept simple on purpose: screens use `setState`, and all server calls are in one file.

```
lib/
├── main.dart                         ← starts the app
├── api.dart                          ← every call to the backend (login, sections, ...)
├── models.dart                       ← User, Section (JSON → Dart)
└── screens/
    ├── start_screen.dart             ← checks if the user is already logged in
    ├── login_screen.dart
    ├── student_home_screen.dart      ← "My courses"
    └── instructor_home_screen.dart   ← "My sections"
```

To add a server call: add a method to `Api` in `api.dart`, then call it from a screen inside
`try { ... } on ApiException catch (e) { ... }`. When the backend refuses something, it
sends a message meant for the user (for example "This QR code has expired."), and
`e.message` is that text, so the screen can just show it.

## Tests and checks

```bash
flutter test
flutter analyze
```

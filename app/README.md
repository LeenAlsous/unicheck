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

**Instructors:** tap a section to start a lecture. The lecture screen shows the rotating QR
code (new code every 10 seconds) and the live list of who checked in (refreshes every 3
seconds; flagged check-ins say what to check, e.g. "weak GPS"). Tap a student to mark them
present / absent / excused, and tap **End lecture** to mark everyone else absent. On a wide
screen (the projector) the QR and the list are side by side.

**Students:** tap **Scan QR code** on "My courses". The app first gets the phone's location
(it asks for permission), then scans the QR on the lecture screen and checks in right away.
If the server refuses, the student sees why (expired code, not in the room, fake location
app...) and can scan again.

To try scanning you need a **real phone** (simulators have no camera). The phone must be on
the same Wi-Fi as the computer running the backend; see the `--dart-define` command above.

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
├── models.dart                       ← User, Section, Lecture, AttendanceList (JSON → Dart)
├── qr_code.dart                      ← makes the rotating QR code (same recipe as the backend)
└── screens/
    ├── start_screen.dart             ← checks if the user is already logged in
    ├── login_screen.dart
    ├── student_home_screen.dart      ← "My courses" + Scan QR code button
    ├── scan_screen.dart              ← camera scanner + location, sends the check-in
    ├── instructor_home_screen.dart   ← "My sections", tap one to start a lecture
    └── lecture_screen.dart           ← QR code + live attendance list
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

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

**Accounts:** instructors add students to a section from an Excel file (section card →
students icon → **Import from Excel**, headings `University ID` and `Name`). New accounts'
first password is their university ID; the app makes them choose a new one at first login.
Anyone can change their password with the lock icon on the home screen.

**Excuses:** each course card has a collapsible **Options** section. Students use it to send an
excuse (day, reason, note, and a photo from the camera or gallery, e.g. a medical report or
exam schedule) and to see their excuses' status. Instructors use it to open the class list
and to review excuses: view the photo, then approve (the student becomes excused for that
day) or reject.

**Phones:** a student can log in on several phones (lost or new phone), but each phone works
for one student only, so you can't log in to a friend's account on your phone. Students see
their phones under **My phones** (the phone icon on "My courses") and can remove a lost or
sold one. Students can't log in on the web version; it is for instructors.

**Students:** when a lecture is running, its course on "My courses" shows a **Check-in open** button; tap it to scan the QR code (pull down to refresh if it isn't showing yet). The app first gets the phone's location
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
├── widgets/card_options.dart         ← the collapsible "Options" on course cards
└── screens/
    ├── start_screen.dart             ← checks if the user is already logged in
    ├── login_screen.dart
    ├── student_home_screen.dart      ← "My courses", Check-in open opens the scanner
    ├── scan_screen.dart              ← camera scanner + location, sends the check-in
    ├── my_phones_screen.dart         ← the student's phones, remove a lost one
    ├── students_screen.dart          ← a section's students + Import from Excel
    ├── change_password_screen.dart   ← change password (required after the first login)
    ├── submit_excuse_screen.dart     ← student sends an excuse with a photo
    ├── excuses_screen.dart           ← list of excuses (student: mine, instructor: all)
    ├── excuse_screen.dart            ← one excuse with its photo; instructor approves or rejects
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

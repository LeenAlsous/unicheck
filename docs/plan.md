# UniCheck plan

## Stack

- **Backend:** Laravel 13 REST API, MySQL, Sanctum bearer tokens
- **App:** one Flutter app for students and instructors. Flutter is needed for
  device-bound keys, mock-location detection, Play Integrity / App Attest and push
  notifications. The instructor screens are also built for web, so the rotating QR can be
  shown from the lecture-room PC on the projector.
- **Hosting:** decided later. Develop locally; phones reach the laptop over the same Wi-Fi
  (`php artisan serve --host=0.0.0.0`) or a tunnel.

### Keeping the backend simple

- The rotating QR is generated **inside the instructor app** from the session's secret
  (like an authenticator app); the server only validates codes. No WebSockets needed.
- The live attendance list **polls** the API every ~3 seconds.

## Anti-fraud layers

| Layer | Stops | Notes |
|---|---|---|
| Rotating QR (new code every ~10 s, HMAC-signed, accept current + previous step) | Screenshots forwarded to friends | Scanning only through the in-app camera, no gallery import |
| Geofence (building radius ~50–100 m, uses GPS accuracy) | Checking in from home | Reject mocked locations; room-level precision comes from the QR, not GPS |
| Phone binding (the phone's built-in ID, sent at login and with every check-in) | One person checking in for several accounts | A student can have several phones (lost or new phone), but each phone belongs to one student only; lost or sold phones can be removed; a new second phone is flagged for 24 hours |
| Short check-in window | Late check-ins after the code spreads | Instructor opens/closes the window |
| Flags + instructor review | Edge cases the checks can't decide | Weak GPS, far from the class cluster, recent device change; random roll-call button |

Known limits (for the report): a friend relaying the QR over a live video call is only
stopped by the location and device layers; GPS spoofing on rooted phones is reduced by
integrity checks but not eliminated.

## Data model (core tables)

users (role: student / instructor / admin) · courses · sections · enrollments · rooms ·
lecture_sessions · attendance_records · devices

## Week 1 — target ~75% by Sat Nov 14, 2026

| Day | Goal by end of day |
|---|---|
| Sat Nov 7 | Repo set up; stack changed to Laravel + Flutter |
| Sun Nov 8 | ✅ Laravel skeleton: tables, seed data, token login with roles, API docs, tests. Next: courses, sessions, QR validation, check-in endpoint |
| Mon Nov 9 | ✅ Backend: sections, start/close lectures, QR check, check-in rules, live list, manual marking. Flutter app skeleton (login, role-based home) ✅ done Fri Nov 13 |
| Tue Nov 10 | ✅ (done Fri Nov 13) Instructor screens: courses, start lecture → rotating QR, live attendance, manual marking, end lecture |
| Wed Nov 11 | ✅ (done Fri Nov 13) Student screens: course list, scanner → check-in with location and fake-location check, phone binding (several phones per student, one student per phone) |
| Thu Nov 12 | Location + mock detection, history; first real classroom test |
| Fri Nov 13 | Absence-limit alerts (FCM), PDF / Excel reports |
| Sat Nov 14 | Bug fixing, full end-to-end demo |

If behind schedule, cut reports first.

## After week 1

Play Integrity / App Attest, suspicious-check-ins tab and random roll call, iOS build,
hosting, pilot lecture (measure time vs manual roll call), report.

# UniCheck plan

## Stack

- **Backend:** ASP.NET Core Web API (.NET 10), EF Core, SignalR, QuestPDF + ClosedXML for exports
- **Instructor portal:** React + TypeScript (Vite)
- **Student app:** Flutter — needed for device-bound keys, mock-location detection,
  Play Integrity / App Attest and push notifications, which a website cannot do reliably
- **Database:** PostgreSQL or SQL Server (open decision — EF Core makes either a small change)
- **Hosting:** laptop + tunnel (Cloudflare Tunnel / ngrok) during development; decide on
  real hosting after the first milestone

## Anti-fraud layers

| Layer | Stops | Notes |
|---|---|---|
| Rotating QR (new code every ~10 s, HMAC-signed, accept current + previous step) | Screenshots forwarded to friends | Scanning only through the in-app camera, no gallery import |
| Geofence (building radius ~50–100 m, uses GPS accuracy) | Checking in from home | Reject mocked locations; room-level precision comes from the QR, not GPS |
| Device binding (key pair in Keystore / Secure Enclave, signed check-ins) | One person checking in for several accounts | One device per account, one account per device per session; device change needs approval |
| Short check-in window | Late check-ins after the code spreads | Instructor opens/closes the window |
| Flags + instructor review | Edge cases the checks can't decide | Weak GPS, far from the class cluster, recent device change; random roll-call button |

Known limits (for the report): a friend relaying the QR over a live video call is only
stopped by the location and device layers; GPS spoofing on rooted phones is reduced by
integrity checks but not eliminated.

## Data model (core tables)

Users (role: Student / Instructor / Admin) · Courses · Sections · Enrollments · Rooms ·
Sessions · AttendanceRecords · Devices · AbsencePolicies · Notifications

## Week 1 — target ~75% by Sat Nov 14, 2026

| Day | Goal by end of day |
|---|---|
| Sat Nov 7 | Repo, backend skeleton, database, login (JWT + roles), seed data; tunnel test from a phone |
| Sun Nov 8 | Courses + student Excel import, sessions, rotating QR (SignalR), check-in endpoint with all server checks |
| Mon Nov 9 | Portal: login, courses, student lists, rooms (map pin), "Start session" → full-screen QR |
| Tue Nov 10 | Portal: live attendance list, manual edits, session history |
| Wed Nov 11 | App: login, device key registration, course list, scanner → check-in |
| Thu Nov 12 | App: location + mock detection, history; first real classroom test |
| Fri Nov 13 | Absence-limit alerts (FCM), PDF / Excel reports |
| Sat Nov 14 | Bug fixing, full end-to-end demo |

If behind schedule, cut reports first.

## After week 1

Play Integrity / App Attest, suspicious-check-ins tab and random roll call, iOS build,
hosting, pilot lecture (measure time vs manual roll call), report.

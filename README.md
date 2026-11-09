# UniCheck

Barcode-based smart attendance tracking for universities. Instructors open a lecture
session and project a QR code that changes every few seconds; students scan it with the
mobile app to register attendance. Location checks, device binding and a short check-in
window make it hard to mark attendance without being in the room.

## Repository layout

| Folder | What it is | Stack |
|---|---|---|
| [`backend/`](backend) | REST API used by both apps | Laravel 13, MySQL, Sanctum |
| [`app/`](app) | One Flutter app for students and instructors | Flutter (Android, iOS, and web for the projector screen) |
| [`docs/`](docs) | Plan, design notes, report material | Markdown |

Each folder has its own README with setup and run instructions.

## Getting started

Requirements:

- PHP 8.3+, [Composer](https://getcomposer.org/) and MySQL (XAMPP on Windows is fine)
- [Flutter](https://docs.flutter.dev/get-started/install) (stable channel)

```bash
git clone https://github.com/LeenAlsous/unicheck.git
cd unicheck
```

Then follow the README in the folder you are working on.

## Working together

- Work on a branch (`feature/<short-name>`) and open a pull request into `main`.
- If a backend change needs a matching app change, put them in the same pull request.
- Never commit secrets (passwords, keys). The backend reads them from `backend/.env`,
  which is not committed; `backend/.env.example` shows the keys.

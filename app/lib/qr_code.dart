import 'dart:convert';

import 'package:crypto/crypto.dart';

// The rotating QR code. This must match backend/app/Services/QrCodeService.php:
//
//   step = floor(unix_seconds / step_seconds)     -> goes up by 1 every 10 seconds
//   hash = first 8 characters of sha256(secret + step)
//   code = "{session_id}-{step}-{hash}"            e.g. "42-179421840-7c8a89cf"

int currentStep(DateTime time, int stepSeconds) {
  final seconds = time.millisecondsSinceEpoch ~/ 1000;
  return seconds ~/ stepSeconds;
}

String makeQrCode(int sessionId, String secret, int step) {
  final hash = sha256.convert(utf8.encode('$secret$step')).toString();
  return '$sessionId-$step-${hash.substring(0, 8)}';
}

import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

/// The phone's location, for checking in.
class LocationHelper {
  /// Asks for location permission (Android/iOS show their popup the first time).
  /// Returns a message to show the user if something is wrong, or null if it's all OK.
  static Future<String?> askPermission() async {
    if (!kIsWeb && !await Geolocator.isLocationServiceEnabled()) {
      return 'Turn on location (GPS) on your phone.';
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      try {
        permission = await Geolocator.requestPermission();
      } catch (e) {
        // The popup is already open (e.g. from the home screen).
        return 'Allow location in the popup, then tap Try again.';
      }
    }
    if (permission == LocationPermission.denied) {
      return 'UniCheck needs your location. Please allow it.';
    }
    if (permission == LocationPermission.deniedForever) {
      return 'Location is blocked for UniCheck. Allow it in Settings.';
    }

    // "Approximate location" (Android 12+, iOS 14+) is too rough to know you're in the room.
    if (!kIsWeb &&
        await Geolocator.getLocationAccuracy() ==
            LocationAccuracyStatus.reduced) {
      return 'Turn on "Precise location" for UniCheck in Settings.';
    }

    return null;
  }

  /// The phone's current location, or null if it can't be found.
  ///
  /// Uses the location the phone found in the last 2 minutes if there is one (instant),
  /// otherwise asks for a new one (can take a few seconds indoors).
  static Future<Position?> getLocation() async {
    final last = await Geolocator.getLastKnownPosition();
    final lastIsRecent =
        last != null && DateTime.now().difference(last.timestamp).inMinutes < 2;
    if (lastIsRecent && last.accuracy <= 100) {
      return last;
    }

    try {
      return await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
    } catch (e) {
      return last; // better an older location than none; the server checks the distance
    }
  }

  /// A precise location, for setting where a section meets.
  ///
  /// Listens to the GPS for up to 10 seconds and keeps the most accurate reading, stopping
  /// early once it's within 20 m. If the GPS gives nothing, uses the location the phone
  /// found in the last 2 minutes. Returns null if there is no location at all.
  static Future<Position?> getPreciseLocation() async {
    Position? best;
    final startedAt = DateTime.now();

    try {
      final positions =
          Geolocator.getPositionStream(
            locationSettings: const LocationSettings(
              accuracy: LocationAccuracy.best,
            ),
          ).timeout(
            const Duration(seconds: 10),
          ); // gives up if no reading comes for 10 s

      await for (final position in positions) {
        if (best == null || position.accuracy < best.accuracy) {
          best = position;
        }
        final tookTooLong =
            DateTime.now().difference(startedAt).inSeconds >= 10;
        if (position.accuracy <= 20 || tookTooLong) {
          break; // good enough, or we've waited long enough
        }
      }
    } catch (e) {
      // No reading in time, or a location error: use what we have.
    }

    if (best != null) return best;

    final last = await Geolocator.getLastKnownPosition();
    if (last != null &&
        DateTime.now().difference(last.timestamp).inMinutes < 2) {
      return last;
    }
    return null;
  }

  /// Opens UniCheck's page in the phone's Settings, to allow location.
  static Future<void> openSettings() async {
    await Geolocator.openAppSettings();
  }
}

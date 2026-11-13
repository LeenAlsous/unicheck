import 'package:flutter/foundation.dart';

/// App settings that change between machines.
abstract final class AppConfig {
  /// Base URL of the Laravel API, ending in `/api`.
  ///
  /// Set it when running on a real phone (use your computer's Wi-Fi IP):
  /// `flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000/api`
  ///
  /// Otherwise the Android emulator uses 10.0.2.2 (the computer running it) and
  /// everything else (iOS simulator, web) uses localhost.
  static String get apiBaseUrl {
    const fromCommandLine = String.fromEnvironment('API_BASE_URL');
    if (fromCommandLine.isNotEmpty) return fromCommandLine;

    final isAndroidEmulator = !kIsWeb && defaultTargetPlatform == TargetPlatform.android;
    return isAndroidEmulator ? 'http://10.0.2.2:8000/api' : 'http://localhost:8000/api';
  }
}

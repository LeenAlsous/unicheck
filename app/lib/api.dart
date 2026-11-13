import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import 'models.dart';

/// An error from the server. [message] can be shown to the user as it is.
class ApiException implements Exception {
  final String message;
  final int statusCode; // 0 if the server couldn't be reached

  ApiException(this.message, this.statusCode);

  @override
  String toString() => message;
}

/// All the calls to the Laravel backend.
class Api {
  /// The backend address. On a real phone, pass your computer's IP:
  /// flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000/api
  static String get baseUrl {
    const fromCommandLine = String.fromEnvironment('API_BASE_URL');
    if (fromCommandLine.isNotEmpty) return fromCommandLine;

    // The Android emulator reaches your computer at 10.0.2.2, not localhost.
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://localhost:8000/api';
  }

  static const _storage = FlutterSecureStorage();

  /// The login token, sent with every request. Null when logged out.
  static String? token;

  static Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  static Future<dynamic> _get(String path) {
    return _send(http.get(Uri.parse(baseUrl + path), headers: _headers));
  }

  static Future<dynamic> _post(String path, [Map<String, dynamic>? body]) {
    return _send(
      http.post(
        Uri.parse(baseUrl + path),
        headers: _headers,
        body: jsonEncode(body ?? {}),
      ),
    );
  }

  static Future<dynamic> _put(String path, Map<String, dynamic> body) {
    return _send(
      http.put(
        Uri.parse(baseUrl + path),
        headers: _headers,
        body: jsonEncode(body),
      ),
    );
  }

  /// Waits for the response and returns its JSON, or throws an [ApiException].
  static Future<dynamic> _send(Future<http.Response> request) async {
    http.Response response;
    try {
      response = await request.timeout(const Duration(seconds: 15));
    } catch (e) {
      throw ApiException(
        "Can't reach the server. Check your internet connection.",
        0,
      );
    }

    final data = response.body.isEmpty ? null : jsonDecode(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    }

    // The backend sends a "message" we can show, e.g. "This QR code has expired."
    if (response.statusCode < 500 && data is Map && data['message'] != null) {
      throw ApiException(data['message'], response.statusCode);
    }
    throw ApiException(
      'Something went wrong on the server. Please try again.',
      response.statusCode,
    );
  }

  /// Logs in and saves the token on the phone, so the user stays logged in.
  static Future<User> login(String universityId, String password) async {
    final data = await _post('/auth/login', {
      'university_id': universityId.trim(),
      'password': password,
      'device_name': 'UniCheck app',
    });

    token = data['token'];
    await _storage.write(key: 'token', value: token);
    return User.fromJson(data['user']);
  }

  /// When the app starts: returns the user if they logged in before, otherwise null.
  static Future<User?> loadSavedLogin() async {
    token = await _storage.read(key: 'token');
    if (token == null) return null;

    try {
      return User.fromJson(await _get('/auth/me'));
    } on ApiException catch (e) {
      // 401 means the saved token expired, so they have to log in again.
      if (e.statusCode == 401) {
        token = null;
        await _storage.delete(key: 'token');
        return null;
      }
      rethrow;
    }
  }

  static Future<void> logout() async {
    try {
      await _post('/auth/logout');
    } catch (e) {
      // Log out on the phone anyway, even without internet.
    }
    token = null;
    await _storage.delete(key: 'token');
  }

  /// Students get the sections they are enrolled in, instructors the ones they teach.
  static Future<List<Section>> getSections() async {
    final List data = await _get('/sections');
    return data.map((json) => Section.fromJson(json)).toList();
  }

  // ---------- Instructors ----------

  /// Starts a lecture (check-in opens for 15 minutes). Returns the lecture's ID.
  static Future<int> startLecture(int sectionId) async {
    final data = await _post('/sections/$sectionId/sessions');
    return data['id'];
  }

  /// A lecture with its QR secret.
  static Future<Lecture> getLecture(int sessionId) async {
    return Lecture.fromJson(await _get('/sessions/$sessionId'));
  }

  /// Who has checked in so far.
  static Future<AttendanceList> getAttendance(int sessionId) async {
    return AttendanceList.fromJson(
      await _get('/sessions/$sessionId/attendance'),
    );
  }

  /// Ends the lecture. Students who didn't check in are marked absent.
  static Future<void> endLecture(int sessionId) async {
    await _post('/sessions/$sessionId/close');
  }

  /// Sets a student's attendance by hand: "present", "absent" or "excused".
  static Future<void> markAttendance(
    int sessionId,
    int studentId,
    String status,
  ) async {
    await _put('/sessions/$sessionId/attendance/$studentId', {
      'status': status,
    });
  }
}

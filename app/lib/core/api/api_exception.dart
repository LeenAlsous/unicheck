import 'package:dio/dio.dart';

/// An API error with a message that can be shown to the user as-is.
class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode});

  /// Turns a Dio error into a readable message.
  ///
  /// The backend puts a user-facing `message` in its JSON errors (for example
  /// "This QR code has expired."), so that is used whenever it is there.
  factory ApiException.fromDio(DioException error) {
    final response = error.response;
    if (response == null) {
      return const ApiException("Can't reach the server. Check your internet connection and try again.");
    }

    final data = response.data;
    final serverMessage = data is Map<String, dynamic> ? data['message'] : null;
    final statusCode = response.statusCode;

    if (serverMessage is String && serverMessage.isNotEmpty && statusCode != 500) {
      return ApiException(serverMessage, statusCode: statusCode);
    }
    return ApiException('Something went wrong on the server. Please try again.', statusCode: statusCode);
  }

  /// Text to show to the user.
  final String message;

  /// HTTP status code, or null when the server couldn't be reached.
  final int? statusCode;

  /// True when the login token is missing, expired or revoked.
  bool get isUnauthorized => statusCode == 401;

  @override
  String toString() => 'ApiException($statusCode): $message';
}

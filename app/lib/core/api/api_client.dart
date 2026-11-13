import 'package:dio/dio.dart';

import '../storage/token_storage.dart';
import 'api_exception.dart';

/// The one HTTP client the app uses to talk to the backend.
///
/// Adds the login token to every request and turns errors into [ApiException]s.
class ApiClient {
  ApiClient({required String baseUrl, required this._tokens, Dio? dio}) : _dio = dio ?? Dio() {
    _dio.options
      ..baseUrl = baseUrl
      ..connectTimeout = const Duration(seconds: 10)
      ..receiveTimeout = const Duration(seconds: 15)
      ..headers = {'Accept': 'application/json'};

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokens.read();
          if (token != null) options.headers['Authorization'] = 'Bearer $token';
          handler.next(options);
        },
        onError: (error, handler) {
          if (error.response?.statusCode == 401) onUnauthorized?.call();
          handler.next(error);
        },
      ),
    );
  }

  final Dio _dio;
  final TokenStorage _tokens;

  /// Called when the server rejects the token, so the app can go back to the login screen.
  void Function()? onUnauthorized;

  /// GET a JSON response.
  Future<T> get<T>(String path) => _send(() => _dio.get<T>(path));

  /// POST JSON and get a JSON response.
  Future<T> post<T>(String path, {Object? data}) => _send(() => _dio.post<T>(path, data: data));

  /// PUT JSON and get a JSON response.
  Future<T> put<T>(String path, {Object? data}) => _send(() => _dio.put<T>(path, data: data));

  Future<T> _send<T>(Future<Response<T>> Function() request) async {
    try {
      final response = await request();
      return response.data as T;
    } on DioException catch (error) {
      throw ApiException.fromDio(error);
    }
  }
}

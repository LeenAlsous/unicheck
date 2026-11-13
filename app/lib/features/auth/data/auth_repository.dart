import '../../../core/api/api_client.dart';
import '../../../core/storage/token_storage.dart';
import '../models/user.dart';

/// Logging in and out.
class AuthRepository {
  AuthRepository({required this._api, required this._tokens});

  final ApiClient _api;
  final TokenStorage _tokens;

  /// Logs in, saves the token, and returns the user.
  ///
  /// Throws an `ApiException` with "Invalid university ID or password." when they don't match.
  Future<User> logIn({required String universityId, required String password}) async {
    final json = await _api.post<Map<String, dynamic>>(
      '/auth/login',
      data: {'university_id': universityId.trim(), 'password': password, 'device_name': 'UniCheck app'},
    );
    await _tokens.save(json['token'] as String);
    return User.fromJson(json['user'] as Map<String, dynamic>);
  }

  /// The user of the saved token, or null if there is no saved token.
  ///
  /// Throws an `ApiException` if the token no longer works or the server can't be reached.
  Future<User?> currentUser() async {
    if (await _tokens.read() == null) return null;
    final json = await _api.get<Map<String, dynamic>>('/auth/me');
    return User.fromJson(json);
  }

  /// Revokes the token on the server (if it can be reached) and forgets it on the phone.
  Future<void> logOut() async {
    try {
      await _api.post<dynamic>('/auth/logout');
    } catch (_) {
      // Logging out must work offline too; the token expires on its own.
    } finally {
      await _tokens.clear();
    }
  }

  /// Forgets the token without calling the server, for when it has already stopped working.
  Future<void> forgetToken() => _tokens.clear();
}

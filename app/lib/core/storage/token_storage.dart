import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Keeps the login token in the phone's secure storage (Keychain / Keystore).
class TokenStorage {
  TokenStorage([FlutterSecureStorage? storage]) : _storage = storage ?? const FlutterSecureStorage();

  static const _key = 'auth_token';

  final FlutterSecureStorage _storage;

  /// The saved token, or null if nobody is logged in.
  Future<String?> read() => _storage.read(key: _key);

  /// Save the token after logging in.
  Future<void> save(String token) => _storage.write(key: _key, value: token);

  /// Forget the token when logging out or when it stops working.
  Future<void> clear() => _storage.delete(key: _key);
}

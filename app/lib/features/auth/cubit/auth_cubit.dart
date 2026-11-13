import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/api/api_exception.dart';
import '../data/auth_repository.dart';
import '../models/user.dart';
import 'auth_state.dart';

/// Who is logged in, for the whole app.
class AuthCubit extends Cubit<AuthState> {
  AuthCubit(this._repository) : super(const AuthChecking());

  final AuthRepository _repository;

  /// On app start: log back in with the saved token, if there is one.
  Future<void> restoreSession() async {
    emit(const AuthChecking());
    try {
      final user = await _repository.currentUser();
      emit(user == null ? const AuthLoggedOut() : AuthLoggedIn(user));
    } on ApiException catch (error) {
      if (error.isUnauthorized) {
        await _repository.forgetToken();
        emit(const AuthLoggedOut());
      } else {
        emit(AuthOffline(error.message));
      }
    }
  }

  /// Called by the login screen after a successful login.
  void loggedIn(User user) => emit(AuthLoggedIn(user));

  /// Log out from the menu.
  Future<void> logOut() async {
    await _repository.logOut();
    emit(const AuthLoggedOut());
  }

  /// The server rejected the token (expired or revoked): go back to the login screen.
  Future<void> sessionExpired() async {
    if (state is! AuthLoggedIn) return;
    await _repository.forgetToken();
    emit(const AuthLoggedOut(message: 'Your session has expired. Please log in again.'));
  }
}

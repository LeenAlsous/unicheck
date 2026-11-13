import 'package:equatable/equatable.dart';

import '../models/user.dart';

/// Whether someone is logged in. Decides which screen the app shows.
sealed class AuthState extends Equatable {
  const AuthState();

  @override
  List<Object?> get props => [];
}

/// Checking the saved token when the app starts.
class AuthChecking extends AuthState {
  const AuthChecking();
}

/// Nobody is logged in: show the login screen.
class AuthLoggedOut extends AuthState {
  const AuthLoggedOut({this.message});

  /// Why the user was logged out, e.g. "Your session has expired.", or null.
  final String? message;

  @override
  List<Object?> get props => [message];
}

/// Someone is logged in: show their home screen.
class AuthLoggedIn extends AuthState {
  const AuthLoggedIn(this.user);

  final User user;

  @override
  List<Object?> get props => [user];
}

/// The saved token couldn't be checked because the server is unreachable.
class AuthOffline extends AuthState {
  const AuthOffline(this.message);

  final String message;

  @override
  List<Object?> get props => [message];
}

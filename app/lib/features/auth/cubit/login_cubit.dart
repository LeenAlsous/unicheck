import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/api/api_exception.dart';
import '../data/auth_repository.dart';
import '../models/user.dart';

/// The login form's state.
class LoginState extends Equatable {
  const LoginState({this.isSubmitting = false, this.error, this.user});

  final bool isSubmitting;

  /// Message to show under the form, or null.
  final String? error;

  /// Set once the login succeeded.
  final User? user;

  @override
  List<Object?> get props => [isSubmitting, error, user];
}

/// Sends the login form.
class LoginCubit extends Cubit<LoginState> {
  LoginCubit(this._repository) : super(const LoginState());

  final AuthRepository _repository;

  /// Log in. On success [LoginState.user] is set; on failure [LoginState.error].
  Future<void> submit({required String universityId, required String password}) async {
    if (state.isSubmitting) return;
    emit(const LoginState(isSubmitting: true));
    try {
      final user = await _repository.logIn(universityId: universityId, password: password);
      emit(LoginState(user: user));
    } on ApiException catch (error) {
      emit(LoginState(error: error.message));
    }
  }
}

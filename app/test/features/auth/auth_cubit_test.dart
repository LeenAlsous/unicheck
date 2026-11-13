import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:unicheck/core/api/api_exception.dart';
import 'package:unicheck/features/auth/cubit/auth_cubit.dart';
import 'package:unicheck/features/auth/cubit/auth_state.dart';
import 'package:unicheck/features/auth/cubit/login_cubit.dart';
import 'package:unicheck/features/auth/data/auth_repository.dart';

import '../../helpers/fixtures.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

void main() {
  late MockAuthRepository repository;

  setUp(() {
    repository = MockAuthRepository();
    when(() => repository.forgetToken()).thenAnswer((_) async {});
    when(() => repository.logOut()).thenAnswer((_) async {});
  });

  group('AuthCubit', () {
    blocTest<AuthCubit, AuthState>(
      'logs back in with the saved token',
      setUp: () => when(() => repository.currentUser()).thenAnswer((_) async => student),
      build: () => AuthCubit(repository),
      act: (cubit) => cubit.restoreSession(),
      expect: () => [const AuthChecking(), const AuthLoggedIn(student)],
    );

    blocTest<AuthCubit, AuthState>(
      'shows the login screen when there is no saved token',
      setUp: () => when(() => repository.currentUser()).thenAnswer((_) async => null),
      build: () => AuthCubit(repository),
      act: (cubit) => cubit.restoreSession(),
      expect: () => [const AuthChecking(), const AuthLoggedOut()],
    );

    blocTest<AuthCubit, AuthState>(
      'forgets a token the server no longer accepts',
      setUp: () =>
          when(() => repository.currentUser()).thenThrow(const ApiException('Unauthenticated.', statusCode: 401)),
      build: () => AuthCubit(repository),
      act: (cubit) => cubit.restoreSession(),
      expect: () => [const AuthChecking(), const AuthLoggedOut()],
      verify: (_) => verify(() => repository.forgetToken()).called(1),
    );

    blocTest<AuthCubit, AuthState>(
      'keeps the token when the server is unreachable',
      setUp: () => when(() => repository.currentUser()).thenThrow(const ApiException("Can't reach the server.")),
      build: () => AuthCubit(repository),
      act: (cubit) => cubit.restoreSession(),
      expect: () => [const AuthChecking(), const AuthOffline("Can't reach the server.")],
      verify: (_) => verifyNever(() => repository.forgetToken()),
    );

    blocTest<AuthCubit, AuthState>(
      'logs out',
      build: () => AuthCubit(repository),
      seed: () => const AuthLoggedIn(student),
      act: (cubit) => cubit.logOut(),
      expect: () => [const AuthLoggedOut()],
    );

    blocTest<AuthCubit, AuthState>(
      'returns to login with a message when the session expires',
      build: () => AuthCubit(repository),
      seed: () => const AuthLoggedIn(student),
      act: (cubit) => cubit.sessionExpired(),
      expect: () => [const AuthLoggedOut(message: 'Your session has expired. Please log in again.')],
    );
  });

  group('LoginCubit', () {
    blocTest<LoginCubit, LoginState>(
      'logs in',
      setUp: () =>
          when(() => repository.logIn(universityId: 'S0001', password: 'secret')).thenAnswer((_) async => student),
      build: () => LoginCubit(repository),
      act: (cubit) => cubit.submit(universityId: 'S0001', password: 'secret'),
      expect: () => [const LoginState(isSubmitting: true), const LoginState(user: student)],
    );

    blocTest<LoginCubit, LoginState>(
      'shows the server message when login fails',
      setUp: () =>
          when(() => repository.logIn(universityId: 'S0001', password: 'wrong'))
              .thenThrow(const ApiException('Invalid university ID or password.', statusCode: 422)),
      build: () => LoginCubit(repository),
      act: (cubit) => cubit.submit(universityId: 'S0001', password: 'wrong'),
      expect: () => [
        const LoginState(isSubmitting: true),
        const LoginState(error: 'Invalid university ID or password.'),
      ],
    );
  });
}

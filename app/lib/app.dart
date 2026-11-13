import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'features/auth/cubit/auth_cubit.dart';
import 'features/auth/cubit/auth_state.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/home/instructor_home_screen.dart';
import 'features/home/student_home_screen.dart';

/// The app's theme and its first screen.
class UniCheckApp extends StatelessWidget {
  const UniCheckApp({super.key});

  static const _brand = Color(0xFF1E5AA8);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'UniCheck',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(colorScheme: ColorScheme.fromSeed(seedColor: _brand)),
      darkTheme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: _brand, brightness: Brightness.dark),
      ),
      home: const AuthGate(),
    );
  }
}

/// Shows the login screen or the right home screen for the logged-in user's role.
class AuthGate extends StatelessWidget {
  const AuthGate({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AuthCubit>().state;
    return switch (state) {
      AuthChecking() => const Scaffold(body: Center(child: CircularProgressIndicator())),
      AuthLoggedOut(:final message) => LoginScreen(notice: message),
      AuthOffline(:final message) => _OfflineScreen(message: message),
      AuthLoggedIn(:final user) when user.role.isStaff => InstructorHomeScreen(user: user),
      AuthLoggedIn(:final user) => StudentHomeScreen(user: user),
    };
  }
}

class _OfflineScreen extends StatelessWidget {
  const _OfflineScreen({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off, size: 48),
              const SizedBox(height: 16),
              Text(message, textAlign: TextAlign.center),
              const SizedBox(height: 16),
              FilledButton(onPressed: context.read<AuthCubit>().restoreSession, child: const Text('Try again')),
            ],
          ),
        ),
      ),
    );
  }
}

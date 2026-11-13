import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import 'instructor_home_screen.dart';
import 'login_screen.dart';
import 'student_home_screen.dart';

/// Opens the right home screen for the user's role.
void openHomeScreen(BuildContext context, User user) {
  final home = user.isStudent
      ? StudentHomeScreen(user: user)
      : InstructorHomeScreen(user: user);

  Navigator.pushReplacement(
    context,
    MaterialPageRoute(builder: (context) => home),
  );
}

/// The first screen: checks if the user is already logged in.
class StartScreen extends StatefulWidget {
  const StartScreen({super.key});

  @override
  State<StartScreen> createState() => _StartScreenState();
}

class _StartScreenState extends State<StartScreen> {
  String? error;

  @override
  void initState() {
    super.initState();
    checkLogin();
  }

  Future<void> checkLogin() async {
    setState(() => error = null);

    try {
      final user = await Api.loadSavedLogin();
      if (!mounted) return;

      if (user != null) {
        openHomeScreen(context, user);
      } else {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const LoginScreen()),
        );
      }
    } on ApiException catch (e) {
      setState(() => error = e.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (error == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off, size: 48),
              const SizedBox(height: 16),
              Text(error!, textAlign: TextAlign.center),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: checkLogin,
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

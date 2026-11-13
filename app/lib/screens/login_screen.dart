import 'package:flutter/material.dart';

import '../api.dart';
import 'start_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final formKey = GlobalKey<FormState>();
  final idController = TextEditingController();
  final passwordController = TextEditingController();

  bool loading = false;
  String? error;

  @override
  void dispose() {
    idController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  Future<void> login() async {
    if (!formKey.currentState!.validate()) return;

    setState(() {
      loading = true;
      error = null;
    });

    try {
      final user = await Api.login(idController.text, passwordController.text);
      if (!mounted) return;
      openHomeScreen(context, user);
    } on ApiException catch (e) {
      setState(() {
        loading = false;
        error = e.message; // e.g. "Invalid university ID or password."
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;

    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 400),
            child: Form(
              key: formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Icon(Icons.qr_code_scanner, size: 64, color: colors.primary),
                  const SizedBox(height: 12),
                  const Text(
                    'UniCheck',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 28),
                  ),
                  const Text(
                    'Log in with your university ID',
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 32),
                  TextFormField(
                    controller: idController,
                    decoration: const InputDecoration(
                      labelText: 'University ID',
                      border: OutlineInputBorder(),
                    ),
                    autocorrect: false,
                    textInputAction: TextInputAction.next,
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Enter your university ID'
                        : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: passwordController,
                    decoration: const InputDecoration(
                      labelText: 'Password',
                      border: OutlineInputBorder(),
                    ),
                    obscureText: true,
                    onFieldSubmitted: (_) => login(),
                    validator: (value) => value == null || value.isEmpty
                        ? 'Enter your password'
                        : null,
                  ),
                  if (error != null) ...[
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(12),
                      color: colors.errorContainer,
                      child: Text(
                        error!,
                        style: TextStyle(color: colors.onErrorContainer),
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: loading ? null : login,
                    child: loading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Log in'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

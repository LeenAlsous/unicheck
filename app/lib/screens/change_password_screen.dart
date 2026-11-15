import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import 'start_screen.dart';

/// Change the password. New accounts (first password = university ID) are sent here
/// right after logging in ([mustChange] is true) and can't skip it.
class ChangePasswordScreen extends StatefulWidget {
  final User user;
  final bool mustChange;

  const ChangePasswordScreen({
    super.key,
    required this.user,
    this.mustChange = false,
  });

  @override
  State<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends State<ChangePasswordScreen> {
  final formKey = GlobalKey<FormState>();
  final currentController = TextEditingController();
  final newController = TextEditingController();
  final confirmController = TextEditingController();

  bool saving = false;
  String? error;

  @override
  void dispose() {
    currentController.dispose();
    newController.dispose();
    confirmController.dispose();
    super.dispose();
  }

  Future<void> save() async {
    if (!formKey.currentState!.validate()) return;

    setState(() {
      saving = true;
      error = null;
    });

    try {
      final user = await Api.changePassword(
        currentController.text,
        newController.text,
      );
      if (!mounted) return;

      if (widget.mustChange) {
        openHomeScreen(context, user);
      } else {
        Navigator.pop(context);
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('Password changed.')));
      }
    } on ApiException catch (e) {
      setState(() {
        saving = false;
        error = e.message; // e.g. "Your current password is wrong."
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Change password'),
        automaticallyImplyLeading:
            !widget.mustChange, // no back button when it's required
      ),
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
                  if (widget.mustChange) ...[
                    Text(
                      'Welcome, ${widget.user.name}! Please choose your own password. '
                      'Your current password is your university ID.',
                    ),
                    const SizedBox(height: 24),
                  ],
                  TextFormField(
                    controller: currentController,
                    decoration: const InputDecoration(
                      labelText: 'Current password',
                      border: OutlineInputBorder(),
                    ),
                    obscureText: true,
                    validator: (value) => value == null || value.isEmpty
                        ? 'Enter your current password'
                        : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: newController,
                    decoration: const InputDecoration(
                      labelText: 'New password',
                      helperText: 'At least 8 characters',
                      border: OutlineInputBorder(),
                    ),
                    obscureText: true,
                    validator: (value) => value == null || value.length < 8
                        ? 'Use at least 8 characters'
                        : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: confirmController,
                    decoration: const InputDecoration(
                      labelText: 'New password again',
                      border: OutlineInputBorder(),
                    ),
                    obscureText: true,
                    onFieldSubmitted: (_) => save(),
                    validator: (value) => value != newController.text
                        ? "The passwords don't match"
                        : null,
                  ),
                  if (error != null) ...[
                    const SizedBox(height: 16),
                    Text(
                      error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: saving ? null : save,
                    child: saving
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Save password'),
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

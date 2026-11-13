import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../cubit/auth_cubit.dart';
import '../cubit/login_cubit.dart';
import '../data/auth_repository.dart';

/// University ID + password login, for students and instructors.
class LoginScreen extends StatelessWidget {
  const LoginScreen({super.key, this.notice});

  /// Shown above the form, e.g. why the user was logged out.
  final String? notice;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => LoginCubit(context.read<AuthRepository>()),
      child: BlocListener<LoginCubit, LoginState>(
        listenWhen: (previous, current) => current.user != null && previous.user == null,
        listener: (context, state) => context.read<AuthCubit>().loggedIn(state.user!),
        child: Scaffold(
          body: SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 400),
                  child: _LoginForm(notice: notice),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _LoginForm extends StatefulWidget {
  const _LoginForm({this.notice});

  final String? notice;

  @override
  State<_LoginForm> createState() => _LoginFormState();
}

class _LoginFormState extends State<_LoginForm> {
  final _formKey = GlobalKey<FormState>();
  final _universityId = TextEditingController();
  final _password = TextEditingController();

  @override
  void dispose() {
    _universityId.dispose();
    _password.dispose();
    super.dispose();
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    context.read<LoginCubit>().submit(universityId: _universityId.text, password: _password.text);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = context.watch<LoginCubit>().state;

    return Form(
      key: _formKey,
      child: AutofillGroup(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Icon(Icons.qr_code_scanner, size: 64, color: theme.colorScheme.primary),
            const SizedBox(height: 12),
            Text('UniCheck', textAlign: TextAlign.center, style: theme.textTheme.headlineMedium),
            const SizedBox(height: 4),
            Text(
              'Log in with your university ID',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
            const SizedBox(height: 32),
            if (widget.notice != null) ...[_Message(text: widget.notice!, isError: false), const SizedBox(height: 16)],
            TextFormField(
              controller: _universityId,
              decoration: const InputDecoration(labelText: 'University ID', border: OutlineInputBorder()),
              textInputAction: TextInputAction.next,
              autocorrect: false,
              autofillHints: const [AutofillHints.username],
              enabled: !state.isSubmitting,
              validator: (value) => (value ?? '').trim().isEmpty ? 'Enter your university ID' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _password,
              decoration: const InputDecoration(labelText: 'Password', border: OutlineInputBorder()),
              obscureText: true,
              textInputAction: TextInputAction.done,
              autofillHints: const [AutofillHints.password],
              enabled: !state.isSubmitting,
              onFieldSubmitted: (_) => _submit(),
              validator: (value) => (value ?? '').isEmpty ? 'Enter your password' : null,
            ),
            if (state.error != null) ...[const SizedBox(height: 16), _Message(text: state.error!, isError: true)],
            const SizedBox(height: 24),
            FilledButton(
              onPressed: state.isSubmitting ? null : _submit,
              style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48)),
              child: state.isSubmitting
                  ? const SizedBox.square(dimension: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Text('Log in'),
            ),
          ],
        ),
      ),
    );
  }
}

class _Message extends StatelessWidget {
  const _Message({required this.text, required this.isError});

  final String text;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Semantics(
      liveRegion: true,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isError ? colors.errorContainer : colors.secondaryContainer,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(text, style: TextStyle(color: isError ? colors.onErrorContainer : colors.onSecondaryContainer)),
      ),
    );
  }
}

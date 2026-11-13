import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'app.dart';
import 'core/api/api_client.dart';
import 'core/config.dart';
import 'core/storage/token_storage.dart';
import 'features/auth/cubit/auth_cubit.dart';
import 'features/auth/data/auth_repository.dart';
import 'features/sections/data/sections_repository.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  final tokens = TokenStorage();
  final api = ApiClient(baseUrl: AppConfig.apiBaseUrl, tokens: tokens);
  final authRepository = AuthRepository(api: api, tokens: tokens);
  final authCubit = AuthCubit(authRepository)..restoreSession();
  api.onUnauthorized = authCubit.sessionExpired;

  runApp(
    MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: authRepository),
        RepositoryProvider(create: (_) => SectionsRepository(api)),
      ],
      child: BlocProvider.value(value: authCubit, child: const UniCheckApp()),
    ),
  );
}

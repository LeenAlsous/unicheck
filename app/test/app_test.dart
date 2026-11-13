import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:unicheck/app.dart';
import 'package:unicheck/core/api/api_exception.dart';
import 'package:unicheck/features/auth/cubit/auth_cubit.dart';
import 'package:unicheck/features/auth/data/auth_repository.dart';
import 'package:unicheck/features/sections/data/sections_repository.dart';
import 'package:unicheck/features/sections/models/section.dart';

import 'helpers/fixtures.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

class MockSectionsRepository extends Mock implements SectionsRepository {}

void main() {
  late MockAuthRepository auth;
  late MockSectionsRepository sections;

  setUp(() {
    auth = MockAuthRepository();
    sections = MockSectionsRepository();
    when(() => auth.currentUser()).thenAnswer((_) async => null);
    when(() => auth.logOut()).thenAnswer((_) async {});
  });

  Future<void> pumpApp(WidgetTester tester) async {
    await tester.pumpWidget(
      MultiRepositoryProvider(
        providers: [
          RepositoryProvider<AuthRepository>.value(value: auth),
          RepositoryProvider<SectionsRepository>.value(value: sections),
        ],
        child: BlocProvider(create: (_) => AuthCubit(auth)..restoreSession(), child: const UniCheckApp()),
      ),
    );
    await tester.pumpAndSettle();
  }

  Future<void> logIn(WidgetTester tester, {required String id, required String password}) async {
    await tester.enterText(find.widgetWithText(TextFormField, 'University ID'), id);
    await tester.enterText(find.widgetWithText(TextFormField, 'Password'), password);
    await tester.tap(find.widgetWithText(FilledButton, 'Log in'));
    await tester.pumpAndSettle();
  }

  testWidgets('asks for both fields before logging in', (tester) async {
    await pumpApp(tester);

    await tester.tap(find.widgetWithText(FilledButton, 'Log in'));
    await tester.pump();

    expect(find.text('Enter your university ID'), findsOneWidget);
    expect(find.text('Enter your password'), findsOneWidget);
    verifyNever(
      () => auth.logIn(
        universityId: any(named: 'universityId'),
        password: any(named: 'password'),
      ),
    );
  });

  testWidgets('shows the error when the password is wrong', (tester) async {
    when(() => auth.logIn(universityId: 'S0001', password: 'wrong'))
        .thenThrow(const ApiException('Invalid university ID or password.', statusCode: 422));
    await pumpApp(tester);

    await logIn(tester, id: 'S0001', password: 'wrong');

    expect(find.text('Invalid university ID or password.'), findsOneWidget);
  });

  testWidgets('a student lands on their courses and sees which have check-in open', (tester) async {
    when(() => auth.logIn(universityId: 'S0001', password: 'secret')).thenAnswer((_) async => student);
    when(() => sections.mySections()).thenAnswer(
      (_) async => [
        Section.fromJson({...sectionJson, 'open_session': openSessionJson}),
      ],
    );
    await pumpApp(tester);

    await logIn(tester, id: 'S0001', password: 'secret');

    expect(find.text('My courses'), findsOneWidget);
    expect(find.text('Database Systems'), findsOneWidget);
    expect(find.text('Check-in open'), findsOneWidget);
  });

  testWidgets('an instructor lands on their sections, and can log out', (tester) async {
    when(() => auth.currentUser()).thenAnswer((_) async => instructor);
    when(() => sections.mySections()).thenAnswer((_) async => [Section.fromJson(sectionJson)]);
    await pumpApp(tester);

    expect(find.text('My sections'), findsOneWidget);
    expect(find.text('8 students · IT Building 101 · 2026-1'), findsOneWidget);

    await tester.tap(find.byTooltip('Account'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Log out'));
    await tester.pumpAndSettle();

    expect(find.widgetWithText(FilledButton, 'Log in'), findsOneWidget);
    verify(() => auth.logOut()).called(1);
  });

  testWidgets('shows a retry button when the courses fail to load', (tester) async {
    when(() => auth.currentUser()).thenAnswer((_) async => student);
    when(() => sections.mySections()).thenThrow(const ApiException("Can't reach the server."));
    await pumpApp(tester);

    expect(find.text("Can't reach the server."), findsOneWidget);
    expect(find.widgetWithText(OutlinedButton, 'Retry'), findsOneWidget);
  });
}

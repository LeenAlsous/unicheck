import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:unicheck/screens/login_screen.dart';

void main() {
  testWidgets('asks for the ID and password before logging in', (tester) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));

    await tester.tap(find.text('Log in'));
    await tester.pump();

    expect(find.text('Enter your university ID'), findsOneWidget);
    expect(find.text('Enter your password'), findsOneWidget);
  });
}

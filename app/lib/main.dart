import 'package:flutter/material.dart';

import 'screens/start_screen.dart';

void main() {
  runApp(const UniCheckApp());
}

class UniCheckApp extends StatelessWidget {
  const UniCheckApp({super.key});

  @override
  Widget build(BuildContext context) {
    const brandColor = Color(0xFF1E5AA8);

    return MaterialApp(
      title: 'UniCheck',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(colorSchemeSeed: brandColor),
      darkTheme: ThemeData(
        colorSchemeSeed: brandColor,
        brightness: Brightness.dark,
      ),
      home: const StartScreen(),
    );
  }
}

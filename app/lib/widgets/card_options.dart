import 'package:flutter/material.dart';

/// The collapsible "Options" row at the bottom of a course card.
/// Tap it to show or hide [children] (usually ListTiles).
class CardOptions extends StatelessWidget {
  final List<Widget> children;

  const CardOptions({super.key, required this.children});

  @override
  Widget build(BuildContext context) {
    return ExpansionTile(
      title: const Text('Options'),
      dense: true,
      // No lines above and below when it opens.
      shape: const Border(),
      collapsedShape: const Border(),
      children: children,
    );
  }
}

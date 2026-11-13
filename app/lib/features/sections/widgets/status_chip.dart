import 'package:flutter/material.dart';

/// Small green "Check-in open" style label.
class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: colors.tertiaryContainer, borderRadius: BorderRadius.circular(12)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.circle, size: 8, color: colors.onTertiaryContainer),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(color: colors.onTertiaryContainer, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}

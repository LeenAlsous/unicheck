import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../cubit/sections_cubit.dart';
import '../models/section.dart';

/// Pull-to-refresh list of sections with loading, error and empty states.
class SectionsList extends StatelessWidget {
  const SectionsList({super.key, required this.emptyMessage, required this.itemBuilder});

  /// Shown when the user has no sections.
  final String emptyMessage;

  /// Builds the card for one section.
  final Widget Function(BuildContext context, Section section) itemBuilder;

  @override
  Widget build(BuildContext context) {
    final state = context.watch<SectionsCubit>().state;
    final sections = state.sections;

    if (sections == null) {
      return state.error == null
          ? const Center(child: CircularProgressIndicator())
          : _CenteredMessage(text: state.error!, onRetry: context.read<SectionsCubit>().load);
    }

    return RefreshIndicator(
      onRefresh: context.read<SectionsCubit>().load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        // Lets pull-to-refresh work even when the list is short.
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          if (state.error != null) ...[
            MaterialBanner(
              content: Text(state.error!),
              actions: [TextButton(onPressed: context.read<SectionsCubit>().load, child: const Text('Retry'))],
            ),
            const SizedBox(height: 12),
          ],
          if (sections.isEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 64),
              child: Text(emptyMessage, textAlign: TextAlign.center),
            ),
          for (final section in sections)
            Padding(padding: const EdgeInsets.only(bottom: 12), child: itemBuilder(context, section)),
        ],
      ),
    );
  }
}

class _CenteredMessage extends StatelessWidget {
  const _CenteredMessage({required this.text, required this.onRetry});

  final String text;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(text, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: onRetry, child: const Text('Retry')),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../auth/models/user.dart';
import '../sections/cubit/sections_cubit.dart';
import '../sections/data/sections_repository.dart';
import '../sections/models/section.dart';
import '../sections/widgets/sections_list.dart';
import '../sections/widgets/status_chip.dart';
import 'home_app_bar.dart';

/// A student's courses, showing which ones have check-in open right now.
class StudentHomeScreen extends StatelessWidget {
  const StudentHomeScreen({super.key, required this.user});

  final User user;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => SectionsCubit(context.read<SectionsRepository>())..load(),
      child: Scaffold(
        appBar: HomeAppBar(title: 'My courses', user: user),
        body: SectionsList(
          emptyMessage: "You aren't enrolled in any courses yet.",
          itemBuilder: (context, section) => _CourseCard(section: section),
        ),
      ),
    );
  }
}

class _CourseCard extends StatelessWidget {
  const _CourseCard({required this.section});

  final Section section;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(section.course.code, style: theme.textTheme.labelLarge?.copyWith(color: theme.colorScheme.primary)),
            const SizedBox(height: 2),
            Text(section.course.name, style: theme.textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(
              '${section.instructorName} · Section ${section.number}',
              style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
            if (section.isCheckInOpen) ...[const SizedBox(height: 12), const StatusChip(label: 'Check-in open')],
          ],
        ),
      ),
    );
  }
}

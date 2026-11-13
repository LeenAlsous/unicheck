import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../auth/models/user.dart';
import '../sections/cubit/sections_cubit.dart';
import '../sections/data/sections_repository.dart';
import '../sections/models/section.dart';
import '../sections/widgets/sections_list.dart';
import '../sections/widgets/status_chip.dart';
import 'home_app_bar.dart';

/// The sections an instructor teaches (admins see all sections).
class InstructorHomeScreen extends StatelessWidget {
  const InstructorHomeScreen({super.key, required this.user});

  final User user;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => SectionsCubit(context.read<SectionsRepository>())..load(),
      child: Scaffold(
        appBar: HomeAppBar(title: user.role == UserRole.admin ? 'All sections' : 'My sections', user: user),
        body: SectionsList(
          emptyMessage: "You don't teach any sections yet.",
          itemBuilder: (context, section) => _SectionCard(section: section),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.section});

  final Section section;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final muted = theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant);
    final students = section.studentsCount == 1 ? '1 student' : '${section.studentsCount} students';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${section.course.code} · Section ${section.number}',
              style: theme.textTheme.labelLarge?.copyWith(color: theme.colorScheme.primary),
            ),
            const SizedBox(height: 2),
            Text(section.course.name, style: theme.textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(
              [students, if (section.defaultRoom != null) section.defaultRoom!.label, section.semester].join(' · '),
              style: muted,
            ),
            if (section.openSession != null) ...[
              const SizedBox(height: 12),
              StatusChip(label: section.isCheckInOpen ? 'Check-in open' : 'Lecture in progress'),
            ],
          ],
        ),
      ),
    );
  }
}

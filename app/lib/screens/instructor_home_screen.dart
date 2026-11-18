import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import 'lecture_screen.dart';
import '../widgets/card_options.dart';
import 'change_password_screen.dart';
import 'excuses_screen.dart';
import 'login_screen.dart';
import 'students_screen.dart';

/// The sections an instructor teaches (admins see all sections).
class InstructorHomeScreen extends StatefulWidget {
  final User user;

  const InstructorHomeScreen({super.key, required this.user});

  @override
  State<InstructorHomeScreen> createState() => _InstructorHomeScreenState();
}

class _InstructorHomeScreenState extends State<InstructorHomeScreen> {
  List<Section> sections = [];
  bool loading = true;
  String? error;

  @override
  void initState() {
    super.initState();
    loadSections();
  }

  Future<void> loadSections() async {
    try {
      final result = await Api.getSections();
      if (!mounted) return;
      setState(() {
        sections = result;
        error = null;
        loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        error = e.message;
        loading = false;
      });
    }
  }

  // Tap a section: continue its running lecture, or start a new one.
  Future<void> openLecture(Section section) async {
    int? sessionId = section.openSessionId;

    if (sessionId == null) {
      final start = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text('Start a lecture for ${section.courseCode}?'),
          content: const Text(
            'A QR code will appear for students to scan. Check-in stays open for 15 minutes.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Start'),
            ),
          ],
        ),
      );
      if (start != true) return;

      try {
        sessionId = await Api.startLecture(section.id);
      } on ApiException catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.message)));
        return;
      }
    }

    if (!mounted) return;
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => LectureScreen(
          sessionId: sessionId!,
          title: '${section.courseCode} - ${section.courseName}',
        ),
      ),
    );
    loadSections(); // the lecture may have ended while we were away
  }

  Future<void> logout() async {
    await Api.logout();
    if (!mounted) return;
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (context) => const LoginScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My sections'),
        actions: [
          IconButton(
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ChangePasswordScreen(user: widget.user),
              ),
            ),
            icon: const Icon(Icons.lock_outline),
            tooltip: 'Change password',
          ),
          IconButton(
            onPressed: logout,
            icon: const Icon(Icons.logout),
            tooltip: 'Log out',
          ),
        ],
      ),
      body: buildBody(),
    );
  }

  Widget buildBody() {
    if (loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(error!, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: loadSections, child: const Text('Retry')),
          ],
        ),
      );
    }

    // Pull down to refresh.
    return RefreshIndicator(
      onRefresh: loadSections,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('Hi, ${widget.user.name}. Tap a section to start a lecture.'),
          const SizedBox(height: 12),
          if (sections.isEmpty) const Text("You don't teach any sections."),
          for (final section in sections)
            Card(
              clipBehavior: Clip.antiAlias,
              child: Column(
                children: [
                  ListTile(
                    title: Text(
                      '${section.courseCode} - ${section.courseName} (section ${section.number})',
                    ),
                    subtitle: Text(
                      '${section.studentsCount} students'
                      '${section.roomName != null ? ', ${section.roomName}' : ''}'
                      ', ${section.semester}',
                    ),
                    trailing: section.hasOpenSession
                        ? const Chip(label: Text('Lecture running'))
                        : const Icon(Icons.play_circle_outline),
                    onTap: () => openLecture(section),
                  ),
                  CardOptions(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.groups_outlined),
                        title: const Text('Students'),
                        subtitle: const Text('Class list, import from Excel'),
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) =>
                                StudentsScreen(section: section),
                          ),
                        ),
                      ),
                      ListTile(
                        leading: const Icon(Icons.assignment_outlined),
                        title: const Text('Excuses'),
                        subtitle: const Text('Review students\' excuses'),
                        onTap: () async {
                          await Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => ExcusesScreen(
                                section: section,
                                isInstructor: true,
                              ),
                            ),
                          );
                          loadSections();
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

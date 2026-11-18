import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../widgets/card_options.dart';
import 'change_password_screen.dart';
import 'excuses_screen.dart';
import 'login_screen.dart';
import 'my_phones_screen.dart';
import 'scan_screen.dart';
import 'submit_excuse_screen.dart';

/// The student's courses. When a lecture is running, its course shows a
/// "Check-in open" button that opens the QR scanner.
class StudentHomeScreen extends StatefulWidget {
  final User user;

  const StudentHomeScreen({super.key, required this.user});

  @override
  State<StudentHomeScreen> createState() => _StudentHomeScreenState();
}

class _StudentHomeScreenState extends State<StudentHomeScreen> {
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

  Future<void> openScanner() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const ScanScreen()),
    );
    loadSections();
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
        title: const Text('My courses'),
        actions: [
          IconButton(
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const MyPhonesScreen()),
            ),
            icon: const Icon(Icons.smartphone),
            tooltip: 'My phones',
          ),
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
          Text('Hi, ${widget.user.name}'),
          const SizedBox(height: 4),
          const Text(
            'When your instructor starts a lecture, pull down to refresh, '
            'then tap "Check-in open" to scan the QR code.',
          ),
          const SizedBox(height: 12),
          if (sections.isEmpty)
            const Text("You aren't enrolled in any courses."),
          for (final section in sections)
            Card(
              clipBehavior: Clip.antiAlias,
              child: Column(
                children: [
                  ListTile(
                    title: Text(
                      '${section.courseCode} - ${section.courseName}',
                    ),
                    subtitle: Text(
                      '${section.instructorName}, section ${section.number}',
                    ),
                    // Tapping the course also opens the scanner while check-in is open.
                    onTap: section.checkInOpen ? openScanner : null,
                  ),
                  // Under the course name, so the name has the full width.
                  if (section.checkInOpen)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: openScanner,
                          icon: const Icon(Icons.qr_code_scanner),
                          label: const Text('Check-in open'),
                        ),
                      ),
                    ),
                  CardOptions(
                    children: [
                      ListTile(
                        leading: Icon(
                          Icons.event_busy,
                          // Red when the student can miss at most one more lecture.
                          color: section.remainingAbsences <= 1
                              ? Colors.red
                              : null,
                        ),
                        title: Text(
                          section.remainingAbsences == 0
                              ? 'No absences left'
                              : 'Remaining absences: ${section.remainingAbsences}',
                        ),
                        subtitle: Text(
                          'You missed ${section.myAbsences} of ${section.maxAbsences} allowed',
                        ),
                      ),
                      ListTile(
                        leading: const Icon(Icons.note_add_outlined),
                        title: const Text('Submit an excuse'),
                        subtitle: const Text('Medical report, exam clash...'),
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) =>
                                SubmitExcuseScreen(section: section),
                          ),
                        ),
                      ),
                      ListTile(
                        leading: const Icon(Icons.history),
                        title: const Text('My excuses'),
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => ExcusesScreen(
                              section: section,
                              isInstructor: false,
                            ),
                          ),
                        ),
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

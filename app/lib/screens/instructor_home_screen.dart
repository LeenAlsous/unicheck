import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import 'login_screen.dart';

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
          const SizedBox(height: 12),
          if (sections.isEmpty) const Text("You don't teach any sections."),
          for (final section in sections)
            Card(
              child: ListTile(
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
                    : null,
              ),
            ),
        ],
      ),
    );
  }
}

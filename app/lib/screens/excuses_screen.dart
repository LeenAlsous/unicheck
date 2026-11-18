import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import 'excuse_screen.dart';

/// A section's excuses. Students see their own; instructors see everyone's and
/// can open one to approve or reject it.
class ExcusesScreen extends StatefulWidget {
  final Section section;
  final bool isInstructor;

  const ExcusesScreen({
    super.key,
    required this.section,
    required this.isInstructor,
  });

  @override
  State<ExcusesScreen> createState() => _ExcusesScreenState();
}

class _ExcusesScreenState extends State<ExcusesScreen> {
  List<Excuse> excuses = [];
  bool loading = true;
  String? error;

  @override
  void initState() {
    super.initState();
    loadExcuses();
  }

  Future<void> loadExcuses() async {
    try {
      final result = await Api.getExcuses(widget.section.id);
      if (!mounted) return;
      setState(() {
        excuses = result;
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

  Future<void> openExcuse(Excuse excuse) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) =>
            ExcuseScreen(excuse: excuse, isInstructor: widget.isInstructor),
      ),
    );
    loadExcuses(); // it may have been approved or rejected
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.isInstructor
              ? '${widget.section.courseCode} excuses'
              : 'My excuses',
        ),
      ),
      body: buildBody(),
    );
  }

  Widget buildBody() {
    if (loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(child: Text(error!, textAlign: TextAlign.center));
    }
    if (excuses.isEmpty) {
      return const Center(child: Text('No excuses yet.'));
    }

    return RefreshIndicator(
      onRefresh: loadExcuses,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          for (final excuse in excuses)
            Card(
              child: ListTile(
                leading: statusIcon(excuse.status),
                title: Text(
                  widget.isInstructor
                      ? '${excuse.studentName} (${excuse.studentUniversityId})'
                      : '${excuse.reasonLabel}, ${excuse.dateLabel}',
                ),
                subtitle: Text(
                  widget.isInstructor
                      ? '${excuse.reasonLabel}, ${excuse.dateLabel} · ${excuse.statusLabel}'
                      : excuse.statusLabel,
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => openExcuse(excuse),
              ),
            ),
        ],
      ),
    );
  }
}

/// Green tick, red cross or grey clock for an excuse's status.
Widget statusIcon(String status) {
  switch (status) {
    case 'approved':
      return const Icon(Icons.check_circle, color: Colors.green);
    case 'rejected':
      return const Icon(Icons.cancel, color: Colors.red);
    default:
      return const Icon(Icons.schedule, color: Colors.grey);
  }
}

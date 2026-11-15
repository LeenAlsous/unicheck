import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';

/// The students in a section. The instructor can add students from an Excel file.
class StudentsScreen extends StatefulWidget {
  final Section section;

  const StudentsScreen({super.key, required this.section});

  @override
  State<StudentsScreen> createState() => _StudentsScreenState();
}

class _StudentsScreenState extends State<StudentsScreen> {
  List<Student> students = [];
  bool loading = true;
  bool importing = false;
  String? error;

  @override
  void initState() {
    super.initState();
    loadStudents();
  }

  Future<void> loadStudents() async {
    try {
      final result = await Api.getStudents(widget.section.id);
      if (!mounted) return;
      setState(() {
        students = result;
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

  Future<void> importFromExcel() async {
    final files = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['xlsx', 'xls', 'csv'],
    );
    if (files.isEmpty) return; // the instructor cancelled

    setState(() => importing = true);

    try {
      final file = files.first;
      final result = await Api.importStudents(
        widget.section.id,
        await file.readAsBytes(),
        file.name,
      );
      await loadStudents();
      showResult(result);
    } on ApiException catch (e) {
      showMessage(e.message);
    }

    if (!mounted) return;
    setState(() => importing = false);
  }

  void showResult(ImportResult result) {
    if (!mounted) return;
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Import finished'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('${result.added} students added to the class.'),
              if (result.alreadyInClass > 0)
                Text('${result.alreadyInClass} were already in the class.'),
              if (result.newAccounts > 0)
                Text(
                  '${result.newAccounts} new accounts were created. '
                  'Their first password is their university ID.',
                ),
              if (result.problems.isNotEmpty) ...[
                const SizedBox(height: 12),
                const Text(
                  'Skipped:',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                for (final problem in result.problems) Text(problem),
              ],
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('${widget.section.courseCode} students')),
      body: buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: importing ? null : importFromExcel,
        icon: importing
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : const Icon(Icons.upload_file),
        label: Text(importing ? 'Importing...' : 'Import from Excel'),
      ),
    );
  }

  Widget buildBody() {
    if (loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (error != null) {
      return Center(child: Text(error!, textAlign: TextAlign.center));
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(
        16,
        16,
        16,
        88,
      ), // room for the Import button
      children: [
        Text(
          '${students.length} students',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 4),
        const Text(
          'To add students, import an Excel or CSV file. The first row must have the headings '
          '"University ID" and "Name", then one student per row.',
        ),
        const SizedBox(height: 12),
        for (final student in students)
          ListTile(
            leading: const Icon(Icons.person_outline),
            title: Text(student.name),
            subtitle: Text(student.universityId),
          ),
      ],
    );
  }
}

import 'dart:typed_data';

import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import 'excuses_screen.dart';

/// One excuse with its photo. Instructors can approve or reject it while it's waiting.
class ExcuseScreen extends StatefulWidget {
  final Excuse excuse;
  final bool isInstructor;

  const ExcuseScreen({
    super.key,
    required this.excuse,
    required this.isInstructor,
  });

  @override
  State<ExcuseScreen> createState() => _ExcuseScreenState();
}

class _ExcuseScreenState extends State<ExcuseScreen> {
  Uint8List? photo;
  String? photoError;
  bool saving = false;

  @override
  void initState() {
    super.initState();
    loadPhoto();
  }

  Future<void> loadPhoto() async {
    try {
      final bytes = await Api.getExcusePhoto(widget.excuse.id);
      if (!mounted) return;
      setState(() => photo = bytes);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => photoError = e.message);
    }
  }

  Future<void> review(String status) async {
    setState(() => saving = true);

    try {
      await Api.reviewExcuse(widget.excuse.id, status);
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            status == 'approved'
                ? 'Approved. The student is marked excused for that day.'
                : 'Rejected.',
          ),
        ),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => saving = false);
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final excuse = widget.excuse;

    return Scaffold(
      appBar: AppBar(title: const Text('Excuse')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (widget.isInstructor)
            Text(
              '${excuse.studentName} (${excuse.studentUniversityId})',
              style: Theme.of(context).textTheme.titleLarge,
            ),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: statusIcon(excuse.status),
            title: Text('${excuse.reasonLabel}, ${excuse.dateLabel}'),
            subtitle: Text(excuse.statusLabel),
          ),
          if (excuse.note != null && excuse.note!.isNotEmpty) ...[
            Text('Note: ${excuse.note}'),
            const SizedBox(height: 16),
          ],
          buildPhoto(),
          if (widget.isInstructor && excuse.status == 'pending') ...[
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: saving ? null : () => review('rejected'),
                    child: const Text('Reject'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    onPressed: saving ? null : () => review('approved'),
                    child: const Text('Approve'),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget buildPhoto() {
    if (photoError != null) {
      return Text(photoError!);
    }
    if (photo == null) {
      return const Center(child: CircularProgressIndicator());
    }
    // Pinch to zoom, to read small text on the document. The height is limited
    // so the Approve and Reject buttons stay on screen.
    return ConstrainedBox(
      constraints: const BoxConstraints(maxHeight: 420),
      child: InteractiveViewer(
        maxScale: 5,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Image.memory(photo!, fit: BoxFit.contain),
        ),
      ),
    );
  }
}

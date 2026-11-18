import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api.dart';
import '../models.dart';

/// A student sends an excuse for missing a lecture: the day, the reason, a note
/// and a photo of the document (e.g. a medical report or the exam schedule).
class SubmitExcuseScreen extends StatefulWidget {
  final Section section;

  const SubmitExcuseScreen({super.key, required this.section});

  @override
  State<SubmitExcuseScreen> createState() => _SubmitExcuseScreenState();
}

class _SubmitExcuseScreenState extends State<SubmitExcuseScreen> {
  DateTime date = DateTime.now();
  String reason = 'medical';
  final noteController = TextEditingController();

  Uint8List? photoBytes;
  String photoName = '';

  bool sending = false;
  String? error;

  @override
  void dispose() {
    noteController.dispose();
    super.dispose();
  }

  Future<void> pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: date,
      firstDate: DateTime.now().subtract(const Duration(days: 60)),
      lastDate: DateTime.now().add(
        const Duration(days: 60),
      ), // e.g. an exam clash known in advance
    );
    if (picked != null) setState(() => date = picked);
  }

  Future<void> pickPhoto(ImageSource source) async {
    // Smaller photo = faster upload; the document is still easy to read.
    final photo = await ImagePicker().pickImage(
      source: source,
      maxWidth: 2000,
      imageQuality: 80,
    );
    if (photo == null) return; // cancelled

    final bytes = await photo.readAsBytes();
    setState(() {
      photoBytes = bytes;
      photoName = photo.name;
    });
  }

  Future<void> send() async {
    if (photoBytes == null) {
      setState(() => error = 'Add a photo of your document.');
      return;
    }

    setState(() {
      sending = true;
      error = null;
    });

    try {
      await Api.submitExcuse(
        sectionId: widget.section.id,
        date: date,
        reason: reason,
        note: noteController.text,
        photoBytes: photoBytes!,
        photoName: photoName,
      );
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Excuse sent. Your instructor will review it.'),
        ),
      );
    } on ApiException catch (e) {
      setState(() {
        sending = false;
        error = e.message;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Excuse for ${widget.section.courseCode}')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.event),
            title: const Text('Day of the lecture'),
            subtitle: Text('${date.day}/${date.month}/${date.year}'),
            trailing: TextButton(
              onPressed: pickDate,
              child: const Text('Change'),
            ),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            initialValue: reason,
            decoration: const InputDecoration(
              labelText: 'Reason',
              border: OutlineInputBorder(),
            ),
            items: const [
              DropdownMenuItem(
                value: 'medical',
                child: Text('Medical (doctor or hospital report)'),
              ),
              DropdownMenuItem(
                value: 'exam',
                child: Text('Exam at the same time'),
              ),
              DropdownMenuItem(value: 'other', child: Text('Other')),
            ],
            onChanged: (value) => setState(() => reason = value!),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: noteController,
            decoration: const InputDecoration(
              labelText: 'Note for your instructor (optional)',
              border: OutlineInputBorder(),
            ),
            maxLines: 3,
            maxLength: 500,
          ),
          const SizedBox(height: 8),
          const Text('Photo of the document'),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => pickPhoto(ImageSource.camera),
                  icon: const Icon(Icons.photo_camera_outlined),
                  label: const Text('Take photo'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => pickPhoto(ImageSource.gallery),
                  icon: const Icon(Icons.photo_library_outlined),
                  label: const Text('Choose photo'),
                ),
              ),
            ],
          ),
          if (photoBytes != null) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.memory(
                photoBytes!,
                height: 220,
                fit: BoxFit.contain,
              ),
            ),
          ],
          if (error != null) ...[
            const SizedBox(height: 16),
            Text(
              error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ],
          const SizedBox(height: 24),
          FilledButton(
            onPressed: sending ? null : send,
            child: sending
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Send excuse'),
          ),
        ],
      ),
    );
  }
}

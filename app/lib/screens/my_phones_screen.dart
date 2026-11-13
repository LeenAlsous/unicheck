import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';

/// The phones the student has logged in from. A lost or sold phone can be removed here:
/// it gets logged out, and another student can then use it.
class MyPhonesScreen extends StatefulWidget {
  const MyPhonesScreen({super.key});

  @override
  State<MyPhonesScreen> createState() => _MyPhonesScreenState();
}

class _MyPhonesScreenState extends State<MyPhonesScreen> {
  List<Phone> phones = [];
  bool loading = true;
  String? error;

  @override
  void initState() {
    super.initState();
    loadPhones();
  }

  Future<void> loadPhones() async {
    try {
      final result = await Api.getMyPhones();
      if (!mounted) return;
      setState(() {
        phones = result;
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

  Future<void> removePhone(Phone phone) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Remove ${phone.name}?'),
        content: const Text(
          'Use this if the phone was lost or sold. It will be logged out of your account, '
          'and you can\'t check in with it anymore.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await Api.removePhone(phone.id);
      loadPhones();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My phones')),
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

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const Text(
          'You can check in from any of these phones. Each phone can only be used by one '
          'student, so your phone can\'t be used to log in to a friend\'s account.',
        ),
        const SizedBox(height: 16),
        for (final phone in phones)
          Card(
            child: ListTile(
              leading: const Icon(Icons.smartphone),
              title: Text(
                phone.isThisPhone ? '${phone.name} (this phone)' : phone.name,
              ),
              subtitle: Text('Added ${formatDate(phone.addedAt)}'),
              trailing: phone.isThisPhone
                  ? null
                  : IconButton(
                      onPressed: () => removePhone(phone),
                      icon: const Icon(Icons.delete_outline),
                      tooltip: 'Remove',
                    ),
            ),
          ),
      ],
    );
  }

  String formatDate(DateTime date) {
    final local = date.toLocal();
    return '${local.day}/${local.month}/${local.year}';
  }
}

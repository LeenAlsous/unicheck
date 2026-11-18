import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../api.dart';
import '../location.dart';
import '../models.dart';

/// The instructor sets where their section meets: the building, the room number, and the
/// GPS point (their current location, taken inside the room). Students must be near this
/// point to check in. Only this section changes.
class SectionLocationScreen extends StatefulWidget {
  final Section section;

  const SectionLocationScreen({super.key, required this.section});

  @override
  State<SectionLocationScreen> createState() => _SectionLocationScreenState();
}

class _SectionLocationScreenState extends State<SectionLocationScreen> {
  late final buildingController = TextEditingController(
    text: widget.section.building ?? '',
  );
  late final roomController = TextEditingController(
    text: widget.section.roomNumber ?? '',
  );

  late double? latitude = widget.section.latitude;
  late double? longitude = widget.section.longitude;
  double? accuracy; // set only when we just used the current location

  bool locating = false;
  bool saving = false;
  String? error;

  @override
  void dispose() {
    buildingController.dispose();
    roomController.dispose();
    super.dispose();
  }

  Future<void> useMyLocation() async {
    setState(() {
      locating = true;
      error = null;
    });

    final problem = await LocationHelper.askPermission();
    if (problem != null) {
      setState(() {
        locating = false;
        error = problem;
      });
      return;
    }

    final position = await LocationHelper.getPreciseLocation();
    if (!mounted) return;
    setState(() {
      locating = false;
      if (position == null) {
        error = "Couldn't find your location. Go near a window and try again.";
      } else {
        latitude = position.latitude;
        longitude = position.longitude;
        accuracy = position.accuracy;
      }
    });
  }

  Future<void> save() async {
    if (buildingController.text.trim().isEmpty ||
        roomController.text.trim().isEmpty) {
      setState(() => error = 'Enter the building and the room number.');
      return;
    }
    if (latitude == null || longitude == null) {
      setState(
        () => error =
            'Stand in the room and tap "Use my current location" first.',
      );
      return;
    }

    setState(() {
      saving = true;
      error = null;
    });

    try {
      await Api.setSectionLocation(
        sectionId: widget.section.id,
        building: buildingController.text.trim(),
        roomNumber: roomController.text.trim(),
        latitude: latitude!,
        longitude: longitude!,
        accuracy: accuracy,
      );
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Section location saved.')));
    } on ApiException catch (e) {
      setState(() {
        saving = false;
        error = e.message; // e.g. "Your location is not precise enough."
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('${widget.section.courseCode} location')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(
            controller: buildingController,
            decoration: const InputDecoration(
              labelText: 'Building',
              hintText: 'e.g. IT Building, Science Faculty',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: roomController,
            decoration: const InputDecoration(
              labelText: 'Room number',
              hintText: 'e.g. 204',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.place_outlined),
            title: const Text('GPS point'),
            subtitle: Text(
              latitude == null
                  ? 'Not set yet'
                  : '${latitude!.toStringAsFixed(5)}, ${longitude!.toStringAsFixed(5)}'
                        '${accuracy != null ? '  (±${accuracy!.round()} m)' : ''}',
            ),
          ),
          const Text(
            'Stand inside the room, then tap the button. Students must be within 75 m of this point.',
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: locating ? null : useMyLocation,
            icon: locating
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.my_location),
            label: Text(
              locating ? 'Finding your location...' : 'Use my current location',
            ),
          ),
          if (error != null) ...[
            const SizedBox(height: 16),
            Text(
              error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
            if (!kIsWeb)
              TextButton(
                onPressed: LocationHelper.openSettings,
                child: const Text('Open Settings'),
              ),
          ],
          const SizedBox(height: 24),
          FilledButton(
            onPressed: saving ? null : save,
            child: saving
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Save'),
          ),
        ],
      ),
    );
  }
}

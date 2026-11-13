import 'dart:async';
import 'dart:math';

import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../api.dart';
import '../models.dart';
import '../qr_code.dart';

/// A running lecture: the rotating QR code for students to scan, and the live list
/// of who has checked in. On a big screen (the projector) they are side by side.
class LectureScreen extends StatefulWidget {
  final int sessionId;
  final String title; // e.g. "CS330 - Database Systems"

  const LectureScreen({
    super.key,
    required this.sessionId,
    required this.title,
  });

  @override
  State<LectureScreen> createState() => _LectureScreenState();
}

class _LectureScreenState extends State<LectureScreen> {
  Lecture? lecture;
  AttendanceList? attendance;
  String? error;

  Timer? clockTimer; // redraws every second (new QR code, countdown)
  Timer? refreshTimer; // reloads the attendance list every 3 seconds

  @override
  void initState() {
    super.initState();
    loadLecture();
    clockTimer = Timer.periodic(
      const Duration(seconds: 1),
      (_) => setState(() {}),
    );
    refreshTimer = Timer.periodic(
      const Duration(seconds: 3),
      (_) => loadAttendance(),
    );
  }

  @override
  void dispose() {
    clockTimer?.cancel();
    refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> loadLecture() async {
    try {
      final result = await Api.getLecture(widget.sessionId);
      if (!mounted) return;
      setState(() => lecture = result);
      loadAttendance();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => error = e.message);
    }
  }

  Future<void> loadAttendance() async {
    try {
      final result = await Api.getAttendance(widget.sessionId);
      if (!mounted) return;
      setState(() => attendance = result);

      // Nothing changes after the lecture ends, so stop reloading.
      if (result.sessionStatus == 'closed') refreshTimer?.cancel();
    } on ApiException {
      // Ignore it: we try again in 3 seconds.
    }
  }

  // The current time on the server (the phone's clock may be wrong).
  DateTime get serverNow =>
      DateTime.now().add(Duration(milliseconds: lecture!.clockOffsetMs));

  bool get isEnded =>
      (attendance?.sessionStatus ?? lecture!.status) == 'closed';

  DateTime? get closesAt => attendance?.closesAt ?? lecture!.closesAt;

  bool get isCheckInOpen =>
      !isEnded && closesAt != null && serverNow.isBefore(closesAt!);

  Future<void> endLecture() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('End lecture?'),
        content: const Text(
          'Students who haven\'t checked in will be marked absent.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('End lecture'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await Api.endLecture(widget.sessionId);
      await loadAttendance();
    } on ApiException catch (e) {
      showMessage(e.message);
    }
  }

  // Tap a student to mark them present / absent / excused by hand.
  Future<void> changeStatus(AttendanceEntry student) async {
    final status = await showDialog<String>(
      context: context,
      builder: (context) => SimpleDialog(
        title: Text(student.name),
        children: [
          for (final option in ['present', 'absent', 'excused'])
            SimpleDialogOption(
              onPressed: () => Navigator.pop(context, option),
              child: Text('Mark ${statusLabel(option).toLowerCase()}'),
            ),
        ],
      ),
    );
    if (status == null) return;

    try {
      await Api.markAttendance(widget.sessionId, student.studentId, status);
      await loadAttendance();
    } on ApiException catch (e) {
      showMessage(e.message);
    }
  }

  void showMessage(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          if (lecture != null && !isEnded)
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: TextButton(
                onPressed: endLecture,
                child: const Text('End lecture'),
              ),
            ),
        ],
      ),
      body: buildBody(),
    );
  }

  Widget buildBody() {
    if (error != null) {
      return Center(child: Text(error!, textAlign: TextAlign.center));
    }
    if (lecture == null) {
      return const Center(child: CircularProgressIndicator());
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        // Big screen (projector or tablet): QR on the left, list on the right.
        if (constraints.maxWidth > 800) {
          final qrSize = min(
            constraints.maxHeight - 140,
            constraints.maxWidth * 0.5,
          );
          return Row(
            children: [
              Expanded(flex: 3, child: Center(child: buildQrPanel(qrSize))),
              const VerticalDivider(width: 1),
              Expanded(flex: 2, child: buildStudentList()),
            ],
          );
        }

        // Phone: QR on top, list below.
        final qrSize = min(constraints.maxWidth - 80, 280.0);
        return Column(
          children: [
            buildQrPanel(qrSize),
            const Divider(height: 1),
            Expanded(child: buildStudentList()),
          ],
        );
      },
    );
  }

  Widget buildQrPanel(double qrSize) {
    final textTheme = Theme.of(context).textTheme;

    if (isEnded) {
      return const Padding(
        padding: EdgeInsets.all(24),
        child: Text(
          'Lecture ended.\nStudents who didn\'t check in were marked absent.',
          textAlign: TextAlign.center,
        ),
      );
    }

    if (!isCheckInOpen) {
      return const Padding(
        padding: EdgeInsets.all(24),
        child: Text(
          'Check-in time is over.\nTap "End lecture" to mark the rest absent.',
          textAlign: TextAlign.center,
        ),
      );
    }

    final step = currentStep(serverNow, lecture!.qrStepSeconds);
    final code = makeQrCode(lecture!.id, lecture!.qrSecret, step);
    final timeLeft = closesAt!.difference(serverNow);
    final minutes = timeLeft.inMinutes;
    final seconds = (timeLeft.inSeconds % 60).toString().padLeft(2, '0');

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // White background so phones can scan it in dark mode too.
          QrImageView(data: code, size: qrSize, backgroundColor: Colors.white),
          const SizedBox(height: 12),
          Text('Scan with the UniCheck app', style: textTheme.titleMedium),
          Text(
            'Check-in closes in $minutes:$seconds'
            '${lecture!.roomName != null ? '  ·  ${lecture!.roomName}' : ''}',
          ),
        ],
      ),
    );
  }

  Widget buildStudentList() {
    final list = attendance;
    if (list == null) {
      return const Center(child: CircularProgressIndicator());
    }

    return ListView(
      padding: const EdgeInsets.symmetric(vertical: 8),
      children: [
        ListTile(
          title: Text(
            '${list.present} of ${list.enrolled} present',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          subtitle: Text(
            '${list.absent} absent, ${list.excused} excused. Tap a student to change.',
          ),
        ),
        for (final student in list.students)
          ListTile(
            leading: statusIcon(student.status),
            title: Text(student.name),
            subtitle: Text(subtitleFor(student)),
            onTap: () => changeStatus(student),
          ),
      ],
    );
  }

  String subtitleFor(AttendanceEntry student) {
    var text = '${student.universityId} · ${statusLabel(student.status)}';
    if (student.flags.isNotEmpty) {
      text += ' · Check: ${student.flags.map(flagLabel).join(', ')}';
    }
    return text;
  }

  Widget statusIcon(String? status) {
    switch (status) {
      case 'present':
        return const Icon(Icons.check_circle, color: Colors.green);
      case 'absent':
        return const Icon(Icons.cancel, color: Colors.red);
      case 'excused':
        return const Icon(Icons.info, color: Colors.orange);
      default:
        return const Icon(Icons.radio_button_unchecked, color: Colors.grey);
    }
  }

  String statusLabel(String? status) {
    switch (status) {
      case 'present':
        return 'Present';
      case 'absent':
        return 'Absent';
      case 'excused':
        return 'Excused';
      default:
        return 'Not checked in yet';
    }
  }

  // Why the server thinks a check-in is worth a second look.
  String flagLabel(String flag) {
    switch (flag) {
      case 'token_near_expiry':
        return 'used an old code';
      case 'weak_location_signal':
        return 'weak GPS';
      case 'far_from_class':
        return 'far from the class';
      default:
        return flag;
    }
  }
}

class User {
  final int id;
  final String universityId; // e.g. S0001 or I1001
  final String name;
  final String role; // "student", "instructor" or "admin"
  final bool
  mustChangePassword; // true for new accounts, until they choose a password

  User({
    required this.id,
    required this.universityId,
    required this.name,
    required this.role,
    required this.mustChangePassword,
  });

  bool get isStudent => role == 'student';

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      universityId: json['university_id'],
      name: json['name'],
      role: json['role'],
      mustChangePassword: json['must_change_password'] == true,
    );
  }
}

class Section {
  final int id;
  final String number;
  final String semester;
  final String courseCode; // e.g. CS330
  final String courseName;
  final String instructorName;
  final int studentsCount;
  final String? roomName; // e.g. "IT Building 101", or null if not set
  final int maxAbsences; // absences allowed in this course
  final int myAbsences; // students only: how many lectures they missed (excused ones don't count)
  final int? openSessionId; // the lecture running right now, or null
  final bool checkInOpen; // students can scan the QR right now

  Section({
    required this.id,
    required this.number,
    required this.semester,
    required this.courseCode,
    required this.courseName,
    required this.instructorName,
    required this.studentsCount,
    required this.roomName,
    required this.maxAbsences,
    required this.myAbsences,
    required this.openSessionId,
    required this.checkInOpen,
  });

  /// How many more lectures the student can miss (never below 0).
  int get remainingAbsences =>
      maxAbsences - myAbsences < 0 ? 0 : maxAbsences - myAbsences;

  bool get hasOpenSession => openSessionId != null;

  factory Section.fromJson(Map<String, dynamic> json) {
    final room = json['default_room'];
    final session = json['open_session'];

    return Section(
      id: json['id'],
      number: json['number'],
      semester: json['semester'],
      courseCode: json['course']['code'],
      courseName: json['course']['name'],
      instructorName: json['instructor']['name'],
      studentsCount: json['students_count'] ?? 0,
      roomName: room == null ? null : '${room['building']} ${room['name']}',
      maxAbsences: json['course']['max_absences'] ?? 0,
      myAbsences: json['my_absences'] ?? 0,
      openSessionId: session?['id'],
      checkInOpen: session != null && session['check_in_open'] == true,
    );
  }
}

/// A lecture, as the instructor sees it (with the secret for the QR code).
class Lecture {
  final int id;
  final String status; // "open" or "closed"
  final DateTime? closesAt; // when check-in ends
  final String? roomName;
  final String qrSecret;
  final int qrStepSeconds; // the QR changes every this many seconds (10)

  // How far the phone's clock is from the server's clock, in milliseconds.
  // The QR code depends on the time, so we use the server's time.
  final int clockOffsetMs;

  Lecture({
    required this.id,
    required this.status,
    required this.closesAt,
    required this.roomName,
    required this.qrSecret,
    required this.qrStepSeconds,
    required this.clockOffsetMs,
  });

  factory Lecture.fromJson(Map<String, dynamic> json) {
    final room = json['room'];
    final int serverTimeMs = json['server_time_ms'];

    return Lecture(
      id: json['id'],
      status: json['status'],
      closesAt: json['closes_at'] == null
          ? null
          : DateTime.parse(json['closes_at']),
      roomName: room == null ? null : '${room['building']} ${room['name']}',
      qrSecret: json['qr_secret'],
      qrStepSeconds: json['qr_step_seconds'],
      clockOffsetMs: serverTimeMs - DateTime.now().millisecondsSinceEpoch,
    );
  }
}

/// One row of the live attendance list.
class AttendanceEntry {
  final int studentId;
  final String universityId;
  final String name;
  final String?
  status; // "present", "absent", "excused", or null = not checked in yet
  final List<String> flags; // e.g. ["weak_location_signal"]

  AttendanceEntry({
    required this.studentId,
    required this.universityId,
    required this.name,
    required this.status,
    required this.flags,
  });

  factory AttendanceEntry.fromJson(Map<String, dynamic> json) {
    final student = json['student'];
    final attendance = json['attendance'];

    return AttendanceEntry(
      studentId: student['id'],
      universityId: student['university_id'],
      name: student['name'],
      status: attendance?['status'],
      flags: List<String>.from(attendance?['flags'] ?? []),
    );
  }
}

/// The live attendance list of a lecture, with totals.
class AttendanceList {
  final String sessionStatus; // "open" or "closed"
  final DateTime? closesAt;
  final int enrolled;
  final int present;
  final int absent;
  final int excused;
  final List<AttendanceEntry> students;

  AttendanceList({
    required this.sessionStatus,
    required this.closesAt,
    required this.enrolled,
    required this.present,
    required this.absent,
    required this.excused,
    required this.students,
  });

  factory AttendanceList.fromJson(Map<String, dynamic> json) {
    final session = json['session'];
    final summary = json['summary'];
    final List students = json['students'];

    return AttendanceList(
      sessionStatus: session['status'],
      closesAt: session['closes_at'] == null
          ? null
          : DateTime.parse(session['closes_at']),
      enrolled: summary['enrolled'],
      present: summary['present'],
      absent: summary['absent'],
      excused: summary['excused'],
      students: students.map((json) => AttendanceEntry.fromJson(json)).toList(),
    );
  }
}

/// One of the student's phones.
class Phone {
  final int id;
  final String name; // e.g. "iPhone"
  final DateTime addedAt;
  final DateTime? lastUsedAt;
  final bool isThisPhone; // the phone the app is running on

  Phone({
    required this.id,
    required this.name,
    required this.addedAt,
    required this.lastUsedAt,
    required this.isThisPhone,
  });

  factory Phone.fromJson(Map<String, dynamic> json) {
    return Phone(
      id: json['id'],
      name: json['name'] ?? 'Phone',
      addedAt: DateTime.parse(json['created_at']),
      lastUsedAt: json['last_used_at'] == null
          ? null
          : DateTime.parse(json['last_used_at']),
      isThisPhone: json['is_this_phone'] == true,
    );
  }
}

/// A student in a class list.
class Student {
  final int id;
  final String universityId;
  final String name;

  Student({required this.id, required this.universityId, required this.name});

  factory Student.fromJson(Map<String, dynamic> json) {
    return Student(
      id: json['id'],
      universityId: json['university_id'],
      name: json['name'],
    );
  }
}

/// What happened when an instructor imported an Excel file of students.
class ImportResult {
  final int added;
  final int alreadyInClass;
  final int newAccounts;
  final List<String> problems; // e.g. "Row 7: no university ID."

  ImportResult({
    required this.added,
    required this.alreadyInClass,
    required this.newAccounts,
    required this.problems,
  });

  factory ImportResult.fromJson(Map<String, dynamic> json) {
    return ImportResult(
      added: json['added'],
      alreadyInClass: json['already_in_class'],
      newAccounts: json['new_accounts'],
      problems: List<String>.from(json['problems']),
    );
  }
}

/// A student's excuse for missing the lectures of a section on one day.
class Excuse {
  final int id;
  final DateTime date;
  final String reason; // "medical", "exam" or "other"
  final String? note;
  final String status; // "pending", "approved" or "rejected"
  final String studentName;
  final String studentUniversityId;

  Excuse({
    required this.id,
    required this.date,
    required this.reason,
    required this.note,
    required this.status,
    required this.studentName,
    required this.studentUniversityId,
  });

  String get reasonLabel {
    switch (reason) {
      case 'medical':
        return 'Medical';
      case 'exam':
        return 'Exam clash';
      default:
        return 'Other';
    }
  }

  String get statusLabel {
    switch (status) {
      case 'approved':
        return 'Approved';
      case 'rejected':
        return 'Rejected';
      default:
        return 'Waiting for review';
    }
  }

  String get dateLabel => '${date.day}/${date.month}/${date.year}';

  factory Excuse.fromJson(Map<String, dynamic> json) {
    final student = json['student'];

    return Excuse(
      id: json['id'],
      date: DateTime.parse(json['date']),
      reason: json['reason'],
      note: json['note'],
      status: json['status'],
      studentName: student?['name'] ?? '',
      studentUniversityId: student?['university_id'] ?? '',
    );
  }
}

import 'package:equatable/equatable.dart';

/// A class section, as returned by `GET /sections`.
class Section extends Equatable {
  const Section({
    required this.id,
    required this.number,
    required this.semester,
    required this.course,
    required this.instructorName,
    required this.studentsCount,
    this.defaultRoom,
    this.openSession,
  });

  factory Section.fromJson(Map<String, dynamic> json) {
    final room = json['default_room'] as Map<String, dynamic>?;
    final session = json['open_session'] as Map<String, dynamic>?;
    return Section(
      id: json['id'] as int,
      number: json['number'] as String,
      semester: json['semester'] as String,
      course: Course.fromJson(json['course'] as Map<String, dynamic>),
      instructorName: (json['instructor'] as Map<String, dynamic>)['name'] as String,
      studentsCount: json['students_count'] as int? ?? 0,
      defaultRoom: room == null ? null : Room.fromJson(room),
      openSession: session == null ? null : OpenSession.fromJson(session),
    );
  }

  final int id;

  /// Section number within the course, e.g. `1`.
  final String number;

  /// e.g. `2026-1`.
  final String semester;
  final Course course;
  final String instructorName;
  final int studentsCount;

  /// Where this section usually meets, if set.
  final Room? defaultRoom;

  /// The lecture running right now, if any.
  final OpenSession? openSession;

  /// True while students can scan the QR for this section.
  bool get isCheckInOpen => openSession?.checkInOpen ?? false;

  @override
  List<Object?> get props => [id, number, semester, course, instructorName, studentsCount, defaultRoom, openSession];
}

/// A course in the catalog.
class Course extends Equatable {
  const Course({required this.id, required this.code, required this.name, required this.maxAbsences});

  factory Course.fromJson(Map<String, dynamic> json) => Course(
    id: json['id'] as int,
    code: json['code'] as String,
    name: json['name'] as String,
    maxAbsences: json['max_absences'] as int,
  );

  final int id;

  /// e.g. `CS330`.
  final String code;
  final String name;

  /// Absences allowed before a warning.
  final int maxAbsences;

  @override
  List<Object?> get props => [id, code, name, maxAbsences];
}

/// A lecture room.
class Room extends Equatable {
  const Room({required this.id, required this.building, required this.name});

  factory Room.fromJson(Map<String, dynamic> json) =>
      Room(id: json['id'] as int, building: json['building'] as String, name: json['name'] as String);

  final int id;
  final String building;
  final String name;

  /// e.g. `IT Building 101`.
  String get label => '$building $name';

  @override
  List<Object?> get props => [id, building, name];
}

/// The lecture that is running now in a section.
class OpenSession extends Equatable {
  const OpenSession({required this.id, required this.checkInOpen, this.closesAt});

  factory OpenSession.fromJson(Map<String, dynamic> json) => OpenSession(
    id: json['id'] as int,
    checkInOpen: json['check_in_open'] as bool,
    closesAt: DateTime.tryParse(json['closes_at'] as String? ?? '')?.toLocal(),
  );

  final int id;

  /// False once the check-in window has ended, even if the lecture hasn't been closed yet.
  final bool checkInOpen;

  /// When check-in ends, in local time.
  final DateTime? closesAt;

  @override
  List<Object?> get props => [id, checkInOpen, closesAt];
}

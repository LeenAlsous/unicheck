class User {
  final int id;
  final String universityId; // e.g. S0001 or I1001
  final String name;
  final String role; // "student", "instructor" or "admin"

  User({
    required this.id,
    required this.universityId,
    required this.name,
    required this.role,
  });

  bool get isStudent => role == 'student';

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      universityId: json['university_id'],
      name: json['name'],
      role: json['role'],
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
  final bool hasOpenSession; // a lecture is running right now
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
    required this.hasOpenSession,
    required this.checkInOpen,
  });

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
      hasOpenSession: session != null,
      checkInOpen: session != null && session['check_in_open'] == true,
    );
  }
}

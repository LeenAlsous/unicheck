import 'package:flutter_test/flutter_test.dart';
import 'package:unicheck/models.dart';

// The JSON here is copied from real backend responses.
void main() {
  test('reads a section', () {
    final section = Section.fromJson({
      'id': 1,
      'number': '1',
      'semester': '2026-1',
      'course': {'id': 1, 'code': 'CS330', 'name': 'Database Systems'},
      'instructor': {'id': 2, 'name': 'Dr. Instructor One'},
      'default_room': {'id': 1, 'building': 'IT Building', 'name': '101'},
      'students_count': 8,
      'open_session': {'id': 7, 'check_in_open': true},
    });

    expect(section.courseCode, 'CS330');
    expect(section.courseName, 'Database Systems');
    expect(section.instructorName, 'Dr. Instructor One');
    expect(section.roomName, 'IT Building 101');
    expect(section.studentsCount, 8);
    expect(section.hasOpenSession, true);
    expect(section.checkInOpen, true);
  });

  test('reads a section with no room and no lecture running', () {
    final section = Section.fromJson({
      'id': 2,
      'number': '1',
      'semester': '2026-1',
      'course': {'id': 2, 'code': 'CS201', 'name': 'Data Structures'},
      'instructor': {'id': 3, 'name': 'Dr. Instructor Two'},
      'default_room': null,
      'students_count': 8,
      'open_session': null,
    });

    expect(section.roomName, null);
    expect(section.hasOpenSession, false);
    expect(section.checkInOpen, false);
  });

  test('reads a user', () {
    final user = User.fromJson({
      'id': 4,
      'university_id': 'S0001',
      'name': 'Student 1',
      'email': null,
      'role': 'student',
    });

    expect(user.universityId, 'S0001');
    expect(user.isStudent, true);
  });
}

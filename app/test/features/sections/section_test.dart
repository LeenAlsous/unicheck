import 'package:flutter_test/flutter_test.dart';
import 'package:unicheck/features/auth/models/user.dart';
import 'package:unicheck/features/sections/models/section.dart';

import '../../helpers/fixtures.dart';

void main() {
  test('reads a section from the API', () {
    final section = Section.fromJson(sectionJson);

    expect(section.course.code, 'CS330');
    expect(section.course.name, 'Database Systems');
    expect(section.instructorName, 'Dr. Instructor One');
    expect(section.defaultRoom?.label, 'IT Building 101');
    expect(section.studentsCount, 8);
    expect(section.openSession, isNull);
    expect(section.isCheckInOpen, isFalse);
  });

  test('reads the open session', () {
    final section = Section.fromJson({...sectionJson, 'default_room': null, 'open_session': openSessionJson});

    expect(section.defaultRoom, isNull);
    expect(section.openSession?.id, 7);
    expect(section.isCheckInOpen, isTrue);
    expect(section.openSession?.closesAt, DateTime.utc(2026, 11, 9, 5, 51, 13).toLocal());
  });

  test('reads a user and their role', () {
    final user = User.fromJson({
      'id': 2,
      'university_id': 'I1001',
      'name': 'Dr. Instructor One',
      'email': null,
      'role': 'instructor',
    });

    expect(user, instructor);
    expect(user.role.isStaff, isTrue);
    expect(UserRole.student.isStaff, isFalse);
  });
}

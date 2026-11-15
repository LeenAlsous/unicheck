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

  test('reads a lecture with its QR secret', () {
    final lecture = Lecture.fromJson({
      'id': 7,
      'status': 'open',
      'closes_at': '2026-11-13T08:48:53.000000Z',
      'room': {'id': 1, 'building': 'IT Building', 'name': '101'},
      'qr_secret': 'abc',
      'qr_step_seconds': 10,
      'server_time_ms': DateTime.now().millisecondsSinceEpoch + 5000,
    });

    expect(lecture.id, 7);
    expect(lecture.closesAt, DateTime.utc(2026, 11, 13, 8, 48, 53));
    expect(lecture.roomName, 'IT Building 101');
    expect(lecture.qrSecret, 'abc');
    // The server clock is 5 seconds ahead of the phone.
    expect(lecture.clockOffsetMs, closeTo(5000, 100));
  });

  test('reads the live attendance list', () {
    final list = AttendanceList.fromJson({
      'session': {'id': 7, 'status': 'open', 'closes_at': null},
      'summary': {
        'enrolled': 2,
        'present': 1,
        'absent': 0,
        'excused': 0,
        'not_checked_in': 1,
      },
      'students': [
        {
          'student': {'id': 4, 'university_id': 'S0001', 'name': 'Student 1'},
          'attendance': {
            'status': 'present',
            'flags': ['weak_location_signal'],
          },
        },
        {
          'student': {'id': 5, 'university_id': 'S0002', 'name': 'Student 2'},
          'attendance': null,
        },
      ],
    });

    expect(list.enrolled, 2);
    expect(list.present, 1);
    expect(list.students[0].status, 'present');
    expect(list.students[0].flags, ['weak_location_signal']);
    expect(list.students[1].status, null);
    expect(list.students[1].flags, isEmpty);
  });

  test('reads a phone', () {
    final phone = Phone.fromJson({
      'id': 3,
      'student_id': 4,
      'name': 'iPhone',
      'last_used_at': '2026-11-13T09:00:00.000000Z',
      'created_at': '2026-11-01T09:00:00.000000Z',
      'updated_at': '2026-11-13T09:00:00.000000Z',
      'is_this_phone': true,
    });

    expect(phone.name, 'iPhone');
    expect(phone.addedAt, DateTime.utc(2026, 11, 1, 9));
    expect(phone.isThisPhone, true);
  });

  test('reads must_change_password', () {
    final user = User.fromJson({
      'id': 9,
      'university_id': 'S0100',
      'name': 'New Student',
      'role': 'student',
      'must_change_password': true,
    });

    expect(user.mustChangePassword, true);
  });

  test('reads an import result', () {
    final result = ImportResult.fromJson({
      'added': 3,
      'already_in_class': 1,
      'new_accounts': 2,
      'problems': ['Row 4: no university ID.'],
    });

    expect(result.added, 3);
    expect(result.problems, ['Row 4: no university ID.']);
  });
}

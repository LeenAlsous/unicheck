import 'package:unicheck/features/auth/models/user.dart';

/// JSON copied from real backend responses, so the models are tested against what the API sends.
const sectionJson = <String, dynamic>{
  'id': 1,
  'number': '1',
  'semester': '2026-1',
  'course': {'id': 1, 'code': 'CS330', 'name': 'Database Systems', 'max_absences': 6},
  'instructor': {'id': 2, 'name': 'Dr. Instructor One'},
  'default_room': {
    'id': 1,
    'building': 'IT Building',
    'name': '101',
    'latitude': 31.9539,
    'longitude': 35.9106,
    'radius_meters': 75,
  },
  'students_count': 8,
  'open_session': null,
};

const openSessionJson = <String, dynamic>{
  'id': 7,
  'section_id': 1,
  'room': null,
  'status': 'open',
  'opened_at': '2026-11-09T05:36:13.000000Z',
  'closes_at': '2026-11-09T05:51:13.000000Z',
  'closed_at': null,
  'check_in_open': true,
  'qr_step_seconds': 10,
};

const student = User(id: 4, universityId: 'S0001', name: 'Student 1', role: UserRole.student);
const instructor = User(id: 2, universityId: 'I1001', name: 'Dr. Instructor One', role: UserRole.instructor);

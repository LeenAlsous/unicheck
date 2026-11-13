import 'package:equatable/equatable.dart';

/// What a user can do in the app.
enum UserRole {
  student,
  instructor,
  admin;

  /// Reads the role string the API sends (`"student"`, `"instructor"`, `"admin"`).
  static UserRole fromJson(String value) => UserRole.values.byName(value);

  /// Instructors and admins get the instructor screens.
  bool get isStaff => this != UserRole.student;
}

/// The logged-in user, from `/auth/login` and `/auth/me`.
class User extends Equatable {
  const User({required this.id, required this.universityId, required this.name, required this.role, this.email});

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: json['id'] as int,
    universityId: json['university_id'] as String,
    name: json['name'] as String,
    email: json['email'] as String?,
    role: UserRole.fromJson(json['role'] as String),
  );

  final int id;

  /// Student or staff number, used to log in (e.g. `S0001`).
  final String universityId;
  final String name;
  final String? email;
  final UserRole role;

  @override
  List<Object?> get props => [id, universityId, name, email, role];
}

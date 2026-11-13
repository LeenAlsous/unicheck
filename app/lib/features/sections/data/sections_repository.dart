import '../../../core/api/api_client.dart';
import '../models/section.dart';

/// Class sections of the logged-in user.
class SectionsRepository {
  SectionsRepository(this._api);

  final ApiClient _api;

  /// Instructors get the sections they teach, students the ones they're enrolled in.
  Future<List<Section>> mySections() async {
    final json = await _api.get<List<dynamic>>('/sections');
    return json.cast<Map<String, dynamic>>().map(Section.fromJson).toList();
  }
}

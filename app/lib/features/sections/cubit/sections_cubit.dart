import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/api/api_exception.dart';
import '../data/sections_repository.dart';
import '../models/section.dart';

/// The home screen's list of sections.
class SectionsState extends Equatable {
  const SectionsState({this.isLoading = false, this.sections, this.error});

  final bool isLoading;

  /// Null until loaded the first time.
  final List<Section>? sections;

  /// Message to show when loading failed, or null.
  final String? error;

  @override
  List<Object?> get props => [isLoading, sections, error];
}

/// Loads and refreshes the user's sections.
class SectionsCubit extends Cubit<SectionsState> {
  SectionsCubit(this._repository) : super(const SectionsState());

  final SectionsRepository _repository;

  /// Load the list. Keeps the old list on screen while refreshing.
  Future<void> load() async {
    emit(SectionsState(isLoading: true, sections: state.sections));
    try {
      emit(SectionsState(sections: await _repository.mySections()));
    } on ApiException catch (error) {
      emit(SectionsState(sections: state.sections, error: error.message));
    }
  }
}

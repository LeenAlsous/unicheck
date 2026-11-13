import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:unicheck/core/api/api_exception.dart';

void main() {
  final request = RequestOptions(path: '/check-ins');

  DioException errorWith(int status, Object? body) => DioException(
    requestOptions: request,
    response: Response(requestOptions: request, statusCode: status, data: body),
  );

  test('uses the message the backend sends', () {
    final error = ApiException.fromDio(
      errorWith(422, {
        'message': 'This QR code has expired. Scan the code on the screen again.',
        'errors': <String, dynamic>{},
      }),
    );

    expect(error.message, 'This QR code has expired. Scan the code on the screen again.');
    expect(error.statusCode, 422);
    expect(error.isUnauthorized, isFalse);
  });

  test('recognises an expired login token', () {
    expect(ApiException.fromDio(errorWith(401, {'message': 'Unauthenticated.'})).isUnauthorized, isTrue);
  });

  test('hides server crash details', () {
    final error = ApiException.fromDio(errorWith(500, {'message': 'SQLSTATE[HY000] ...'}));

    expect(error.message, 'Something went wrong on the server. Please try again.');
  });

  test('explains when the server cannot be reached', () {
    final error = ApiException.fromDio(DioException.connectionError(requestOptions: request, reason: 'refused'));

    expect(error.message, contains("Can't reach the server"));
    expect(error.statusCode, isNull);
  });
}

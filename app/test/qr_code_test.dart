import 'package:flutter_test/flutter_test.dart';
import 'package:unicheck/qr_code.dart';

void main() {
  // Same example as backend/tests/Unit/QrCodeServiceTest.php, so the app and the
  // server are sure to make the same code.
  test('makes the same code as the backend', () {
    final time = DateTime.utc(2026, 11, 9, 10, 0, 0);
    final step = currentStep(time, 10);

    expect(step, 179421840);
    expect(makeQrCode(42, 'test-secret', step), '42-179421840-7c8a89cf');
  });

  test('the code changes every 10 seconds', () {
    final time = DateTime.utc(2026, 11, 9, 10, 0, 0);

    expect(currentStep(time.add(const Duration(seconds: 9)), 10), 179421840);
    expect(currentStep(time.add(const Duration(seconds: 10)), 10), 179421841);
  });
}

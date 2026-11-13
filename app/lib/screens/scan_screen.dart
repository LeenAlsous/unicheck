import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../api.dart';

/// The student scans the QR code on the lecture screen to check in.
///
/// We get the location first, so when a code is scanned we can send it right away
/// (the code only works for 10–20 seconds).
class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  final scanner = MobileScannerController(formats: [BarcodeFormat.qrCode]);

  Position? position; // the phone's location, null until we have it
  String? locationError;

  bool sending = false; // true while sending a code, so we only send one
  bool? success; // null = still scanning, true/false = result
  String resultMessage = '';

  @override
  void initState() {
    super.initState();
    getLocation();
  }

  @override
  void dispose() {
    scanner.dispose();
    super.dispose();
  }

  Future<void> getLocation() async {
    setState(() => locationError = null);

    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        setState(() => locationError = 'Turn on location (GPS) to check in.');
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        setState(
          () => locationError = 'UniCheck needs your location to check you in. Allow it in Settings.',
        );
        return;
      }

      final result = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.best,
          timeLimit: Duration(seconds: 20),
        ),
      );
      if (!mounted) return;
      setState(() => position = result);
    } catch (e) {
      if (!mounted) return;
      setState(() => locationError = "Couldn't find your location. Try again.");
    }
  }

  Future<void> onDetect(BarcodeCapture capture) async {
    // Wait until we know the location, and only send one code at a time.
    if (sending || position == null) return;

    final code = capture.barcodes.first.rawValue;
    if (code == null) return;

    setState(() => sending = true);
    await scanner.stop();

    try {
      await Api.checkIn(
        code: code,
        latitude: position!.latitude,
        longitude: position!.longitude,
        accuracy: position!.accuracy,
        isMocked:
            position!.isMocked, // true if a fake-location app is used (Android)
      );
      showResult(true, "You're checked in!");
    } on ApiException catch (e) {
      // e.g. "This QR code has expired." or "You need to be in the lecture room to check in."
      showResult(false, e.message);
    }
  }

  void showResult(bool ok, String message) {
    if (!mounted) return;
    setState(() {
      sending = false;
      success = ok;
      resultMessage = message;
    });
  }

  Future<void> scanAgain() async {
    setState(() => success = null);
    await scanner.start();
    getLocation(); // the student may have moved
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Scan QR code')),
      body: success == null ? buildScanner() : buildResult(),
    );
  }

  Widget buildScanner() {
    return Column(
      children: [
        Expanded(
          child: MobileScanner(
            controller: scanner,
            onDetect: onDetect,
            errorBuilder: (context, error) => Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  error.errorCode == MobileScannerErrorCode.permissionDenied
                      ? 'UniCheck needs the camera to scan the QR code. Allow it in Settings.'
                      : "Couldn't open the camera.",
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(16),
          child: buildLocationStatus(),
        ),
      ],
    );
  }

  Widget buildLocationStatus() {
    if (sending) {
      return const Text('Checking you in...');
    }
    if (locationError != null) {
      return Column(
        children: [
          Text(locationError!, textAlign: TextAlign.center),
          TextButton(onPressed: getLocation, child: const Text('Try again')),
        ],
      );
    }
    if (position == null) {
      return const Text('Finding your location...');
    }
    return const Text('Point the camera at the QR code on the screen.');
  }

  Widget buildResult() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              success! ? Icons.check_circle : Icons.error,
              color: success! ? Colors.green : Colors.red,
              size: 80,
            ),
            const SizedBox(height: 16),
            Text(
              resultMessage,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 24),
            if (success!)
              FilledButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Done'),
              )
            else
              FilledButton(
                onPressed: scanAgain,
                child: const Text('Scan again'),
              ),
          ],
        ),
      ),
    );
  }
}

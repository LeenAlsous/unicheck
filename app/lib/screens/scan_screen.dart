import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../api.dart';
import '../location.dart';

/// The student scans the QR code on the lecture screen to check in.
///
/// Order matters: location permission first, then the camera (Android can only show one
/// permission popup at a time), then the location itself. When a code is scanned we
/// already know where the phone is, so it's sent right away (the code only works for 10–20 s).
class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  // The camera starts in prepare(), after the location permission.
  final scanner = MobileScannerController(
    formats: [BarcodeFormat.qrCode],
    autoStart: false,
  );

  Position? position; // the phone's location, null until we have it
  String? locationError;

  bool sending = false; // true while sending a code, so we only send one
  bool? success; // null = still scanning, true/false = result
  String resultMessage = '';

  @override
  void initState() {
    super.initState();
    prepare();
  }

  @override
  void dispose() {
    scanner.dispose();
    super.dispose();
  }

  Future<void> prepare() async {
    setState(() {
      locationError = null;
      position = null;
    });

    // 1. Location permission (usually already given on the home screen).
    final problem = await LocationHelper.askPermission();
    if (!mounted) return;
    if (problem != null) {
      setState(() => locationError = problem);
      return;
    }

    // 2. The camera (asks for camera permission the first time).
    try {
      await scanner.start();
    } catch (e) {
      // The camera's own error message is shown by MobileScanner's errorBuilder.
    }

    // 3. Where the phone is.
    final result = await LocationHelper.getLocation();
    if (!mounted) return;
    setState(() {
      position = result;
      if (result == null) {
        locationError =
            "Couldn't find your location. Go near a window and try again.";
      }
    });
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
    prepare(); // turns the camera back on and gets the location again
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
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              TextButton(onPressed: prepare, child: const Text('Try again')),
              TextButton(
                onPressed: LocationHelper.openSettings,
                child: const Text('Open Settings'),
              ),
            ],
          ),
        ],
      );
    }
    if (position == null) {
      return const Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: 16,
            height: 16,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          SizedBox(width: 12),
          Text('Finding your location...'),
        ],
      );
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

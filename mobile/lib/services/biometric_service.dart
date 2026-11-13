import 'package:local_auth/local_auth.dart';

/// Gerbang biometrik untuk login ulang tanpa kata sandi.
/// Seluruh kegagalan platform dibungkus menjadi `false` agar UI tetap jalan
/// di emulator tanpa sensor.
class BiometricService {
  final LocalAuthentication _auth;
  BiometricService([LocalAuthentication? auth]) : _auth = auth ?? LocalAuthentication();

  Future<bool> canUseBiometrics() async {
    try {
      final supported = await _auth.isDeviceSupported();
      if (!supported) return false;
      final available = await _auth.getAvailableBiometrics();
      return available.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  Future<bool> authenticate() async {
    try {
      return await _auth.authenticate(
        localizedReason: 'Verifikasi identitas untuk masuk SIMAK',
        biometricOnly: true,
        persistAcrossBackgrounding: true,
      );
    } catch (_) {
      return false;
    }
  }
}

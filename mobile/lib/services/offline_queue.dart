import 'dart:convert';
import 'dart:math';

import 'package:shared_preferences/shared_preferences.dart';

/// Antrean offline absensi QR: tiap item membawa `idempotency_key` (UUID
/// dari sisi klien) sehingga replay ke POST /absensi/scan aman dari duplikat.
class OfflineQueue {
  static const storageKey = 'absensi_offline_queue';

  /// Tambah token QR ke antrean; kembalikan kunci idempotensi yang dipakai.
  static Future<String> enqueue(String qrToken, {String? idempotencyKey}) async {
    final prefs = await SharedPreferences.getInstance();
    final items = await pending();
    final key = idempotencyKey ?? newKey();
    items.add({'qr_token': qrToken, 'idempotency_key': key, 'queued_at': DateTime.now().toIso8601String()});
    await prefs.setString(storageKey, jsonEncode(items));
    return key;
  }

  static Future<List<Map<String, dynamic>>> pending() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(storageKey);
    if (raw == null || raw.isEmpty) return [];
    final decoded = jsonDecode(raw) as List;
    return decoded.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  static Future<void> removeKeys(Set<String> keys) async {
    final prefs = await SharedPreferences.getInstance();
    final items = await pending();
    items.removeWhere((e) => keys.contains(e['idempotency_key']));
    await prefs.setString(storageKey, jsonEncode(items));
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(storageKey);
  }

  /// Kunci idempotensi unik sisi klien (UUID v4, entropi acak + waktu).
  static String newKey() {
    final r = Random.secure();
    String hex(int bytes) =>
        List.generate(bytes, (_) => r.nextInt(256)).map((b) => b.toRadixString(16).padLeft(2, '0')).join();
    final time = DateTime.now().microsecondsSinceEpoch.toRadixString(16).padLeft(12, '0');
    return '${hex(4)}-${hex(2)}-4${hex(2).substring(1)}-8${hex(2).substring(1)}-${(time + hex(6)).substring(0, 12)}';
  }
}

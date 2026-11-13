import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// Cache KTM offline: snapshot /me terakhir agar kartu identitas tetap
/// tampil tanpa koneksi.
class KtmCache {
  static const key = 'ktm_snapshot';

  static Future<void> save(dynamic me) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(key, jsonEncode(me));
  }

  static Future<Map<String, dynamic>?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(key);
    if (raw == null || raw.isEmpty) return null;
    return Map<String, dynamic>.from(jsonDecode(raw) as Map);
  }
}

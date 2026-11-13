import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'api_config.dart';
import 'services/offline_queue.dart';

/// Klien API SIMAK: simpan token Sanctum, panggil endpoint /api/v1.
class ApiClient {
  static const _tokenKey = 'sanctum_token';

  static Future<String?> token() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  static Future<Map<String, String>> _headers({bool auth = true}) async {
    final headers = {'Accept': 'application/json', 'Content-Type': 'application/json'};
    if (auth) {
      final t = await token();
      if (t != null) headers['Authorization'] = 'Bearer $t';
    }
    return headers;
  }

  static dynamic _decode(http.Response res) {
    try {
      return {'status': res.statusCode, 'body': res.body.isEmpty ? {} : jsonDecode(res.body)};
    } catch (_) {
      return {'status': res.statusCode, 'body': {'message': res.body}};
    }
  }

  static Future<Map<String, dynamic>> login(String id, String password) async {
    final res = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/login'),
      headers: await _headers(auth: false),
      body: jsonEncode({'nim_or_email': id, 'password': password}),
    );
    final body = jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode == 200 && body['token'] != null) {
      await saveToken(body['token'] as String);
    }
    return {'status': res.statusCode, 'body': body};
  }

  static Future<dynamic> get(String path) async {
    try {
      final res = await http.get(Uri.parse('${ApiConfig.baseUrl}$path'), headers: await _headers());
      return _decode(res);
    } catch (e) {
      return {
        'status': 0,
        'body': {'message': 'offline', 'error': '$e'}
      };
    }
  }

  static Future<dynamic> post(String path, Map<String, dynamic> data) async {
    try {
      final res = await http.post(Uri.parse('${ApiConfig.baseUrl}$path'),
          headers: await _headers(), body: jsonEncode(data));
      return _decode(res);
    } catch (e) {
      return {
        'status': 0,
        'body': {'message': 'offline', 'error': '$e'}
      };
    }
  }

  /// POST dengan header Idempotency-Key (dipakai absensi QR + replay antrean).
  static Future<dynamic> postIdempotent(String path, Map<String, dynamic> data, String key) async {
    try {
      final headers = await _headers();
      headers['Idempotency-Key'] = key;
      final res = await http.post(Uri.parse('${ApiConfig.baseUrl}$path'),
          headers: headers, body: jsonEncode({...data, 'idempotency_key': key}));
      return _decode(res);
    } catch (e) {
      return {
        'status': 0,
        'body': {'message': 'offline', 'error': '$e'}
      };
    }
  }

  /// Kirim absensi; saat offline (status 0) masuk antrean dengan kunci baru.
  static Future<Map<String, dynamic>> kirimAbsensi(String qrToken) async {
    final key = OfflineQueue.newKey();
    final r = await postIdempotent('/absensi/scan', {'qr_token': qrToken}, key);
    if ((r['status'] as int) == 0) {
      await OfflineQueue.enqueue(qrToken, idempotencyKey: key);
      return {'status': 0, 'queued': true, 'idempotency_key': key};
    }
    return Map<String, dynamic>.from(r as Map);
  }

  /// Replay seluruh antrean offline; kembalikan jumlah yang berhasil.
  static Future<int> flushAntreanAbsensi() async {
    final items = await OfflineQueue.pending();
    final done = <String>{};
    for (final item in items) {
      final r = await postIdempotent('/absensi/scan', {'qr_token': '${item['qr_token']}'},
          '${item['idempotency_key']}');
      final status = r['status'] as int;
      if (status == 201 || status == 200) done.add('${item['idempotency_key']}');
    }
    await OfflineQueue.removeKeys(done);
    return done.length;
  }

  /// Upload berkas tugas (multipart) — dipakai file picker tugas.
  static Future<dynamic> uploadTugas(int tugasId, String filename, List<int> bytes,
      {String? catatan}) async {
    try {
      final req =
          http.MultipartRequest('POST', Uri.parse('${ApiConfig.baseUrl}/tugas/$tugasId/kumpul'));
      final headers = await _headers();
      req.headers.addAll(headers);
      req.files.add(http.MultipartFile.fromBytes('file', bytes, filename: filename));
      if (catatan != null && catatan.isNotEmpty) req.fields['catatan'] = catatan;
      final streamed = await req.send();
      final res = await http.Response.fromStream(streamed);
      return _decode(res);
    } catch (e) {
      return {
        'status': 0,
        'body': {'message': 'offline', 'error': '$e'}
      };
    }
  }

  /// Daftarkan device token FCM + terima topik prodi dari server.
  static Future<dynamic> registerDeviceToken(
      {required String token, String platform = 'android', String? appVersion}) async {
    return post('/device-token', {
      'fcm_token': token,
      'platform': platform,
      if (appVersion != null) 'app_version': appVersion,
    });
  }
}

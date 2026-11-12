import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'api_config.dart';

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
    final res = await http.get(Uri.parse('${ApiConfig.baseUrl}$path'), headers: await _headers());
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }

  static Future<dynamic> post(String path, Map<String, dynamic> data) async {
    final res = await http.post(Uri.parse('${ApiConfig.baseUrl}$path'),
        headers: await _headers(), body: jsonEncode(data));
    return {'status': res.statusCode, 'body': jsonDecode(res.body)};
  }
}

import 'package:flutter/material.dart';

import '../api_client.dart';
import '../l10n/strings.dart';
import '../services/biometric_service.dart';
import '../services/locale_prefs.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _id = TextEditingController();
  final _pass = TextEditingController();
  String _error = '';
  bool _busy = false;
  bool _bioAvailable = false;
  AppLocale _locale = AppLocale.id;

  @override
  void initState() {
    super.initState();
    LocalePrefs.load().then((l) => mounted ? setState(() => _locale = l) : null);
    BiometricService().canUseBiometrics().then((v) => mounted ? setState(() => _bioAvailable = v) : null);
  }

  Strings get s => Strings(_locale);

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = '';
    });
    final res = await ApiClient.login(_id.text.trim(), _pass.text);
    setState(() => _busy = false);
    if (!mounted) return;
    if (res['status'] == 200) {
      Navigator.pushReplacementNamed(context, '/shell');
    } else {
      setState(() => _error = (res['body']['message'] ?? 'Login gagal').toString());
    }
  }

  Future<void> _bioLogin() async {
    final ok = await BiometricService().authenticate();
    if (!mounted) return;
    if (ok && await ApiClient.token() != null) {
      Navigator.pushReplacementNamed(context, '/shell');
    } else if (mounted) {
      setState(() => _error = 'Biometrik gagal / belum ada sesi.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(s.get('login')),
        actions: [
          TextButton(
            onPressed: () async {
              final next = _locale == AppLocale.id ? AppLocale.en : AppLocale.id;
              await LocalePrefs.save(next);
              if (mounted) setState(() => _locale = next);
            },
            child: Text(_locale == AppLocale.id ? 'EN' : 'ID'),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _id, decoration: InputDecoration(labelText: s.get('nimEmail'))),
            TextField(
                controller: _pass,
                decoration: InputDecoration(labelText: s.get('password')),
                obscureText: true),
            if (_error.isNotEmpty)
              Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error, style: const TextStyle(color: Colors.red))),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _busy ? null : _submit, child: Text(_busy ? '...' : s.get('masuk'))),
            if (_bioAvailable) ...[
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: _bioLogin,
                icon: const Icon(Icons.fingerprint),
                label: Text(s.get('masukBiometrik')),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../api_client.dart';

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

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = '';
    });
    final res = await ApiClient.login(_id.text.trim(), _pass.text);
    setState(() => _busy = false);
    if (!mounted) return;
    if (res['status'] == 200) {
      Navigator.pushReplacementNamed(context, '/home');
    } else {
      setState(() => _error = (res['body']['message'] ?? 'Login gagal').toString());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login SIMAK')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _id, decoration: const InputDecoration(labelText: 'NIM / Email')),
            TextField(controller: _pass, decoration: const InputDecoration(labelText: 'Password'), obscureText: true),
            if (_error.isNotEmpty)
              Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error, style: const TextStyle(color: Colors.red))),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _busy ? null : _submit, child: Text(_busy ? '...' : 'Masuk')),
          ],
        ),
      ),
    );
  }
}

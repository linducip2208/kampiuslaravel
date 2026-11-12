import 'package:flutter/material.dart';

import '../api_client.dart';

/// Absensi: tempel/masukkan token QR pertemuan (kamera penuh butuh device).
class QrScanScreen extends StatefulWidget {
  const QrScanScreen({super.key});

  @override
  State<QrScanScreen> createState() => _QrScanScreenState();
}

class _QrScanScreenState extends State<QrScanScreen> {
  final _token = TextEditingController();
  String _info = '';

  Future<void> _kirim() async {
    final r = await ApiClient.post('/absensi/scan', {'qr_token': _token.text.trim()});
    if (!mounted) return;
    setState(() => _info = r['status'] == 201 ? 'Kehadiran tercatat.' : (r['body']['message'] ?? 'Gagal').toString());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Absensi QR')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _token, decoration: const InputDecoration(labelText: 'Token QR pertemuan')),
            const SizedBox(height: 12),
            ElevatedButton(onPressed: _kirim, child: const Text('Kirim Kehadiran')),
            if (_info.isNotEmpty) Padding(padding: const EdgeInsets.only(top: 12), child: Text(_info)),
          ],
        ),
      ),
    );
  }
}

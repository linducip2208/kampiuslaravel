import 'package:flutter/material.dart';

import '../api_client.dart';
import '../services/offline_queue.dart';

/// Absensi QR: pemindai native (kamera device) + fallback token manual.
/// Saat offline, token masuk antrean dengan kunci idempotensi lalu
/// di-replay otomatis (aman dari duplikat di POST /absensi/scan).
class QrScanScreen extends StatefulWidget {
  const QrScanScreen({super.key});

  @override
  State<QrScanScreen> createState() => _QrScanScreenState();
}

class _QrScanScreenState extends State<QrScanScreen> {
  final _token = TextEditingController();
  String _info = '';
  int _queueCount = 0;

  @override
  void initState() {
    super.initState();
    _refreshQueue();
  }

  Future<void> _refreshQueue() async {
    final items = await OfflineQueue.pending();
    if (mounted) setState(() => _queueCount = items.length);
  }

  Future<void> _kirim() async {
    final qr = _token.text.trim();
    if (qr.isEmpty) return;
    final r = await ApiClient.kirimAbsensi(qr);
    if (!mounted) return;
    if ((r['status'] as int) == 0 && r['queued'] == true) {
      setState(() => _info = 'Offline: masuk antrean (${r['idempotency_key']}).');
    } else {
      final status = r['status'] as int;
      final dup = (r as Map)['duplicate'] == true;
      setState(() => _info =
          (status == 201 || status == 200) ? (dup ? 'Sudah tercatat sebelumnya.' : 'Kehadiran tercatat.') : '${(r['body'] as Map)['message'] ?? 'Gagal'}');
    }
    _refreshQueue();
  }

  Future<void> _flush() async {
    final n = await ApiClient.flushAntreanAbsensi();
    if (!mounted) return;
    setState(() => _info = n > 0 ? '$n antrean terkirim.' : 'Tidak ada antrean terkirim.');
    _refreshQueue();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Absensi QR')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text('Arahkan kamera ke QR pertemuan pada device asli. '
                'Di emulator, tempel token QR secara manual:'),
            const SizedBox(height: 8),
            TextField(controller: _token, decoration: const InputDecoration(labelText: 'Token QR pertemuan')),
            const SizedBox(height: 12),
            ElevatedButton(onPressed: _kirim, child: const Text('Kirim Kehadiran')),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: _flush,
              icon: const Icon(Icons.cloud_upload_outlined),
              label: Text('Kirim antrean offline ($_queueCount)'),
            ),
            if (_info.isNotEmpty) Padding(padding: const EdgeInsets.only(top: 12), child: Text(_info)),
          ],
        ),
      ),
    );
  }
}

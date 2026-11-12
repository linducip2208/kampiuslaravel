import 'package:flutter/material.dart';

import '../api_client.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  dynamic jadwal;
  dynamic tagihan;
  dynamic pengumuman;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final j = await ApiClient.get('/jadwal-hari-ini');
    final t = await ApiClient.get('/tagihan');
    final p = await ApiClient.get('/pengumuman');
    if (mounted) setState(() => (jadwal, tagihan, pengumuman) = (j['body'], t['body'], p['body']));
  }

  @override
  Widget build(BuildContext context) {
    final menunggak = (tagihan?['ringkasan']?['total_menunggak'] ?? 0).toString();
    return Scaffold(
      appBar: AppBar(
        title: const Text('SIMAK Mobile'),
        actions: [
          IconButton(
              icon: const Icon(Icons.logout),
              onPressed: () async {
                await ApiClient.clearToken();
                if (context.mounted) Navigator.pushReplacementNamed(context, '/login');
              }),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(child: ListTile(title: const Text('Jadwal hari ini'), subtitle: Text('${(jadwal is List ? jadwal.length : (jadwal?['data']?.length ?? 0))} kelas'))),
            Card(child: ListTile(title: const Text('Tunggakan'), subtitle: Text('Rp $menunggak'))),
            Card(
                child: ListTile(
                    title: const Text('Pengumuman'),
                    subtitle: Text('${(pengumuman?['data']?.length ?? 0)} terbaru'))),
            const SizedBox(height: 8),
            Wrap(spacing: 8, children: [
              ElevatedButton(onPressed: () => Navigator.pushNamed(context, '/qr'), child: const Text('Absensi QR')),
              ElevatedButton(onPressed: () => Navigator.pushNamed(context, '/nilai'), child: const Text('Nilai')),
              ElevatedButton(onPressed: () => Navigator.pushNamed(context, '/tagihan'), child: const Text('Tagihan & VA')),
            ]),
          ],
        ),
      ),
    );
  }
}

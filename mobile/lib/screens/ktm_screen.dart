import 'package:flutter/material.dart';

import '../api_client.dart';
import '../services/ktm_cache.dart';

/// KTM digital + cache offline: pakai snapshot /me terakhir bila offline.
class KtmScreen extends StatefulWidget {
  const KtmScreen({super.key});

  @override
  State<KtmScreen> createState() => _KtmScreenState();
}

class _KtmScreenState extends State<KtmScreen> {
  Map<String, dynamic>? me;
  bool offline = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ApiClient.get('/me');
    if (!mounted) return;
    if ((r['status'] as int) == 0) {
      final cached = await KtmCache.load();
      setState(() {
        me = cached;
        offline = true;
      });
    } else {
      final body = Map<String, dynamic>.from(r['body'] as Map);
      await KtmCache.save(body);
      setState(() {
        me = body;
        offline = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = me?['data'] ?? me ?? {};
    final name = '${user['name'] ?? user['nama'] ?? '-'}';
    final nim = '${user['nim'] ?? (user['mahasiswa'] is Map ? (user['mahasiswa'] as Map)['nim'] : null) ?? '-'}';
    final prodi =
        '${user['prodi'] ?? (user['mahasiswa'] is Map ? (user['mahasiswa'] as Map)['prodi'] : null) ?? '-'}';
    return Scaffold(
      appBar: AppBar(title: const Text('KTM Digital')),
      body: me == null
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (offline)
                    const Card(
                        color: Color(0xFFFFF3E0),
                        child: ListTile(
                            leading: Icon(Icons.wifi_off),
                            title: Text('Mode offline — data KTM dari cache.'))),
                  Card(
                    color: const Color(0xFF1A237E),
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('KARTU TANDA MAHASISWA',
                              style: TextStyle(color: Colors.white70, letterSpacing: 2, fontSize: 12)),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              const CircleAvatar(
                                  radius: 28,
                                  backgroundColor: Colors.white24,
                                  child: Icon(Icons.person, color: Colors.white, size: 32)),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(name,
                                        style: const TextStyle(
                                            color: Colors.white,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 18)),
                                    Text('NIM $nim', style: const TextStyle(color: Colors.white70)),
                                    Text('$prodi', style: const TextStyle(color: Colors.white70)),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          const Text('Tunjukkan layar ini sebagai identitas kampus.',
                              style: TextStyle(color: Colors.white54, fontSize: 12)),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

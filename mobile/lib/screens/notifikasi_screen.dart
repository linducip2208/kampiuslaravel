import 'package:flutter/material.dart';

import '../api_client.dart';

class NotifikasiScreen extends StatefulWidget {
  const NotifikasiScreen({super.key});

  @override
  State<NotifikasiScreen> createState() => _NotifikasiScreenState();
}

class _NotifikasiScreenState extends State<NotifikasiScreen> {
  dynamic data;
  String _topic = '-';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ApiClient.get('/notifikasi');
    if (mounted) setState(() => data = r['body']);
  }

  Future<void> _read(int id) async {
    await ApiClient.post('/notifikasi/$id/read', {});
    _load();
  }

  /// Daftarkan token FCM device ke POST /device-token (topik per prodi
  /// dihitung server dari mahasiswa login).
  Future<void> _aktifkan() async {
    final r = await ApiClient.registerDeviceToken(token: 'simulated-fcm-token', platform: 'android');
    if (!mounted) return;
    setState(() => _topic = '${(r['body'] as Map?)?['topic'] ?? '-'}');
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Topik: $_topic')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final list = (data?['data'] ?? []) as List;
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifikasi'),
        actions: [
          IconButton(icon: const Icon(Icons.notifications_active_outlined), onPressed: _aktifkan),
        ],
      ),
      body: data == null
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: list.length,
                itemBuilder: (_, i) {
                  final n = list[i];
                  final unread = n['dibaca_at'] == null;
                  return Card(
                    color: unread ? Colors.indigo.shade50 : null,
                    child: ListTile(
                      title: Text('${n['judul']}', style: TextStyle(fontWeight: unread ? FontWeight.bold : FontWeight.normal)),
                      subtitle: Text('${n['isi'] ?? ''}'),
                      trailing: unread ? const Icon(Icons.mark_email_unread) : null,
                      onTap: () => _read(n['id'] as int),
                    ),
                  );
                },
              ),
            ),
    );
  }
}

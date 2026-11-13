import 'package:flutter/material.dart';

import '../api_client.dart';

class BimbinganScreen extends StatefulWidget {
  const BimbinganScreen({super.key});

  @override
  State<BimbinganScreen> createState() => _BimbinganScreenState();
}

class _BimbinganScreenState extends State<BimbinganScreen> {
  dynamic data;
  String _info = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ApiClient.get('/bimbingan');
    if (mounted) setState(() => data = r['body']);
  }

  @override
  Widget build(BuildContext context) {
    final list = (data?['data'] ?? data ?? []) as List;
    return Scaffold(
      appBar: AppBar(title: const Text('Bimbingan')),
      body: data == null
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_info.isNotEmpty) Card(child: Padding(padding: const EdgeInsets.all(12), child: Text(_info))),
                  ...list.map((b) => Card(
                        child: ListTile(
                          title: Text('${b['catatan'] ?? '-'}'),
                          subtitle: Text('${b['tanggal'] ?? ''} • ${b['status'] ?? ''}'),
                        ),
                      )),
                ],
              ),
            ),
    );
  }
}

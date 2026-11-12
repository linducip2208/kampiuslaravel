import 'package:flutter/material.dart';

import '../api_client.dart';

class TagihanScreen extends StatefulWidget {
  const TagihanScreen({super.key});

  @override
  State<TagihanScreen> createState() => _TagihanScreenState();
}

class _TagihanScreenState extends State<TagihanScreen> {
  dynamic data;
  String _info = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final r = await ApiClient.get('/tagihan');
    if (mounted) setState(() => data = r['body']);
  }

  Future<void> _buatVa(int id) async {
    final r = await ApiClient.post('/tagihan/$id/va', {});
    if (!mounted) return;
    setState(() => _info = r['status'] == 201
        ? 'VA: ${r['body']['va_number']} (Ref ${r['body']['reference']})'
        : (r['body']['message'] ?? 'Gagal membuat VA').toString());
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final list = (data?['data'] ?? []) as List;
    return Scaffold(
      appBar: AppBar(title: const Text('Tagihan & VA')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_info.isNotEmpty) Card(child: Padding(padding: const EdgeInsets.all(12), child: Text(_info))),
          ...list.map((t) => Card(
                  child: ListTile(
                      title: Text('${t['jenis']} — Rp ${t['nominal']}'),
                      subtitle: Text('Status: ${t['status']}'),
                      trailing: t['status'] != 'lunas'
                          ? ElevatedButton(onPressed: () => _buatVa(t['id'] as int), child: const Text('Buat VA'))
                          : null))),
        ],
      ),
    );
  }
}

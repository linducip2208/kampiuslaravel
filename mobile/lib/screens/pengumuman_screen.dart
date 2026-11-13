import 'package:flutter/material.dart';

import '../api_client.dart';

class PengumumanScreen extends StatefulWidget {
  const PengumumanScreen({super.key});

  @override
  State<PengumumanScreen> createState() => _PengumumanScreenState();
}

class _PengumumanScreenState extends State<PengumumanScreen> {
  dynamic data;

  @override
  void initState() {
    super.initState();
    ApiClient.get('/pengumuman').then((r) => mounted ? setState(() => data = r['body']) : null);
  }

  @override
  Widget build(BuildContext context) {
    final list = (data?['data'] ?? []) as List;
    return Scaffold(
      appBar: AppBar(title: const Text('Pengumuman')),
      body: data == null
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () async {
                final r = await ApiClient.get('/pengumuman');
                if (mounted) setState(() => data = r['body']);
              },
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: list.length,
                itemBuilder: (_, i) => Card(
                  child: ListTile(
                    title: Text('${list[i]['judul']}'),
                    subtitle: Text('${list[i]['published_at'] ?? ''}'),
                  ),
                ),
              ),
            ),
    );
  }
}

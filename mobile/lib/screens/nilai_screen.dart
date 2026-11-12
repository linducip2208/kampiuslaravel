import 'package:flutter/material.dart';

import '../api_client.dart';

class NilaiScreen extends StatefulWidget {
  const NilaiScreen({super.key});

  @override
  State<NilaiScreen> createState() => _NilaiScreenState();
}

class _NilaiScreenState extends State<NilaiScreen> {
  dynamic data;

  @override
  void initState() {
    super.initState();
    ApiClient.get('/nilai-ringkas').then((r) => mounted ? setState(() => data = r['body']) : null);
  }

  @override
  Widget build(BuildContext context) {
    final nilai = (data?['nilai'] ?? data?['data'] ?? []) as List;
    return Scaffold(
      appBar: AppBar(title: const Text('Nilai & IPK')),
      body: data == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(child: ListTile(title: const Text('IPK'), subtitle: Text('${data['ipk'] ?? '-'}'))),
                ...nilai.map((n) => Card(
                        child: ListTile(
                            title: Text('${n['kode_mk'] ?? n['matakuliah'] ?? '-'}'),
                            trailing: Text('${n['huruf'] ?? '-'}')))),
              ],
            ),
    );
  }
}
